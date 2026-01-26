"""
Streaming Tracker - Parse Traefik logs to determine active streaming sessions
"""

import os
import re
import json
from datetime import datetime, timedelta
from collections import defaultdict


class StreamingTracker:
    def __init__(self):
        # Traefik access log path (mount this in Docker)
        self.log_path = os.environ.get('TRAEFIK_ACCESS_LOG', '/var/log/traefik/access.log')

        # Streaming endpoints to track (customize based on your setup)
        self.streaming_patterns = os.environ.get('STREAMING_PATTERNS',
            '/stream,/play,/video,/hls,/dash,/manifest').split(',')

        # Time window for "active" streams (default: 5 minutes)
        self.active_window_minutes = int(os.environ.get('ACTIVE_WINDOW_MINUTES', '5'))

        # Cache for performance
        self._cache = {
            'last_position': 0,
            'active_ips': {},
            'last_check': None
        }

    def get_active_streams(self):
        """
        Get current streaming activity metrics
        Returns count of unique IPs streaming in the last N minutes
        """
        now = datetime.now()
        cutoff_time = now - timedelta(minutes=self.active_window_minutes)

        # Parse recent log entries
        active_sessions = self._parse_recent_logs(cutoff_time)

        return {
            'active_count': len(active_sessions['unique_ips']),
            'unique_ips': list(active_sessions['unique_ips']),
            'requests_count': active_sessions['total_requests'],
            'by_endpoint': active_sessions['by_endpoint'],
            'window_minutes': self.active_window_minutes,
            'timestamp': now.isoformat()
        }

    def _parse_recent_logs(self, cutoff_time):
        """Parse Traefik access logs for recent streaming activity"""
        result = {
            'unique_ips': set(),
            'total_requests': 0,
            'by_endpoint': defaultdict(int)
        }

        if not os.path.exists(self.log_path):
            # Return simulated data if log file doesn't exist
            return self._get_simulated_data()

        try:
            with open(self.log_path, 'r') as f:
                # Read from last known position for efficiency
                # For simplicity, we'll read the last 10000 lines
                f.seek(0, 2)  # Go to end
                file_size = f.tell()

                # Read last chunk of file (approx 1MB)
                chunk_size = min(file_size, 1024 * 1024)
                f.seek(max(0, file_size - chunk_size))

                # Skip partial first line
                if f.tell() > 0:
                    f.readline()

                for line in f:
                    parsed = self._parse_log_line(line)
                    if not parsed:
                        continue

                    # Check if within time window
                    if parsed['timestamp'] < cutoff_time:
                        continue

                    # Check if it's a streaming request
                    if self._is_streaming_request(parsed['path']):
                        result['unique_ips'].add(parsed['client_ip'])
                        result['total_requests'] += 1

                        # Categorize by endpoint
                        for pattern in self.streaming_patterns:
                            if pattern in parsed['path']:
                                result['by_endpoint'][pattern] += 1
                                break

        except Exception as e:
            print(f"Error parsing Traefik logs: {e}")
            return self._get_simulated_data()

        return result

    def _parse_log_line(self, line):
        """
        Parse a single Traefik access log line
        Traefik can output in different formats (Common, JSON, etc.)
        """
        line = line.strip()
        if not line:
            return None

        # Try JSON format first (recommended for Traefik)
        if line.startswith('{'):
            try:
                data = json.loads(line)
                return {
                    'client_ip': data.get('ClientAddr', data.get('clientIP', '')).split(':')[0],
                    'timestamp': self._parse_timestamp(data.get('StartUTC', data.get('time', ''))),
                    'method': data.get('RequestMethod', data.get('request_method', '')),
                    'path': data.get('RequestPath', data.get('request_uri', '')),
                    'status': data.get('DownstreamStatus', data.get('status', 0)),
                    'duration': data.get('Duration', 0)
                }
            except json.JSONDecodeError:
                pass

        # Try Common Log Format
        # Format: IP - - [timestamp] "METHOD /path HTTP/x.x" status size
        common_pattern = r'^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) (\S+) [^"]*" (\d+)'
        match = re.match(common_pattern, line)
        if match:
            return {
                'client_ip': match.group(1),
                'timestamp': self._parse_clf_timestamp(match.group(2)),
                'method': match.group(3),
                'path': match.group(4),
                'status': int(match.group(5)),
                'duration': 0
            }

        return None

    def _parse_timestamp(self, ts_string):
        """Parse ISO format timestamp"""
        if not ts_string:
            return datetime.min
        try:
            # Handle various ISO formats
            ts_string = ts_string.replace('Z', '+00:00')
            if '.' in ts_string:
                # Truncate microseconds if too long
                parts = ts_string.split('.')
                if len(parts[1]) > 6:
                    ts_string = parts[0] + '.' + parts[1][:6] + parts[1][-6:]
            return datetime.fromisoformat(ts_string.replace('+00:00', ''))
        except Exception:
            return datetime.min

    def _parse_clf_timestamp(self, ts_string):
        """Parse Common Log Format timestamp: 26/Jan/2026:10:30:00 +0000"""
        try:
            return datetime.strptime(ts_string.split()[0], '%d/%b/%Y:%H:%M:%S')
        except Exception:
            return datetime.min

    def _is_streaming_request(self, path):
        """Check if request path matches streaming patterns"""
        if not path:
            return False
        path_lower = path.lower()
        return any(pattern.lower() in path_lower for pattern in self.streaming_patterns)

    def _get_simulated_data(self):
        """Return simulated data when logs aren't available"""
        return {
            'unique_ips': set(),
            'total_requests': 0,
            'by_endpoint': {}
        }

    def get_streaming_history(self, hours=24):
        """Get streaming activity over time (for charts)"""
        # This would require more sophisticated log parsing
        # For now, return placeholder structure
        return {
            'hours': hours,
            'data_points': [],
            'message': 'Historical data requires log aggregation setup'
        }
