"""
Stats Collector - System-wide statistics collection
"""

import psutil
import os
from datetime import datetime


class StatsCollector:
    def __init__(self):
        self.disk_paths = os.environ.get('MONITOR_DISKS', '/').split(',')

    def get_system_stats(self):
        """Get comprehensive system statistics"""
        return {
            'cpu': self._get_cpu_stats(),
            'memory': self._get_memory_stats(),
            'disk': self._get_disk_stats(),
            'network': self._get_network_stats(),
            'load': self._get_load_average(),
            'uptime': self._get_uptime(),
            'timestamp': datetime.utcnow().isoformat()
        }

    def _get_cpu_stats(self):
        """Get CPU usage statistics"""
        cpu_percent = psutil.cpu_percent(interval=0.1, percpu=False)
        cpu_percent_per_core = psutil.cpu_percent(interval=0.1, percpu=True)
        cpu_freq = psutil.cpu_freq()
        cpu_count = psutil.cpu_count()
        cpu_count_logical = psutil.cpu_count(logical=True)

        return {
            'percent': cpu_percent,
            'percent_per_core': cpu_percent_per_core,
            'frequency_current': cpu_freq.current if cpu_freq else 0,
            'frequency_max': cpu_freq.max if cpu_freq else 0,
            'cores_physical': cpu_count,
            'cores_logical': cpu_count_logical
        }

    def _get_memory_stats(self):
        """Get memory usage statistics"""
        mem = psutil.virtual_memory()
        swap = psutil.swap_memory()

        return {
            'total': mem.total,
            'available': mem.available,
            'used': mem.used,
            'percent': mem.percent,
            'swap_total': swap.total,
            'swap_used': swap.used,
            'swap_percent': swap.percent
        }

    def _get_disk_stats(self):
        """Get disk usage statistics for monitored paths"""
        disks = {}

        for path in self.disk_paths:
            path = path.strip()
            if os.path.exists(path):
                try:
                    usage = psutil.disk_usage(path)
                    disks[path] = {
                        'total': usage.total,
                        'used': usage.used,
                        'free': usage.free,
                        'percent': usage.percent
                    }
                except Exception as e:
                    disks[path] = {'error': str(e)}

        # Get disk I/O stats
        try:
            io_counters = psutil.disk_io_counters()
            if io_counters:
                disks['io'] = {
                    'read_bytes': io_counters.read_bytes,
                    'write_bytes': io_counters.write_bytes,
                    'read_count': io_counters.read_count,
                    'write_count': io_counters.write_count
                }
        except Exception:
            pass

        return disks

    def _get_network_stats(self):
        """Get network I/O statistics"""
        net_io = psutil.net_io_counters()

        # Get per-interface stats
        net_io_per_nic = psutil.net_io_counters(pernic=True)
        interfaces = {}

        for nic, stats in net_io_per_nic.items():
            # Skip loopback
            if nic == 'lo':
                continue
            interfaces[nic] = {
                'bytes_sent': stats.bytes_sent,
                'bytes_recv': stats.bytes_recv,
                'packets_sent': stats.packets_sent,
                'packets_recv': stats.packets_recv,
                'errors_in': stats.errin,
                'errors_out': stats.errout
            }

        return {
            'total': {
                'bytes_sent': net_io.bytes_sent,
                'bytes_recv': net_io.bytes_recv,
                'packets_sent': net_io.packets_sent,
                'packets_recv': net_io.packets_recv
            },
            'interfaces': interfaces
        }

    def _get_load_average(self):
        """Get system load average"""
        try:
            load = os.getloadavg()
            return {
                '1min': round(load[0], 2),
                '5min': round(load[1], 2),
                '15min': round(load[2], 2)
            }
        except Exception:
            return {'1min': 0, '5min': 0, '15min': 0}

    def _get_uptime(self):
        """Get system uptime in seconds"""
        boot_time = psutil.boot_time()
        uptime_seconds = datetime.now().timestamp() - boot_time
        return {
            'seconds': int(uptime_seconds),
            'boot_time': datetime.fromtimestamp(boot_time).isoformat()
        }

    def format_bytes(self, bytes_value):
        """Format bytes to human-readable string"""
        for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
            if bytes_value < 1024:
                return f"{bytes_value:.2f} {unit}"
            bytes_value /= 1024
        return f"{bytes_value:.2f} PB"
