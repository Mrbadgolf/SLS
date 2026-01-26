"""
Backup Manager - Restic-based backup and restore operations
"""

import os
import subprocess
import json
from datetime import datetime


class BackupManager:
    def __init__(self):
        self.restic_repo = os.environ.get('RESTIC_REPOSITORY', '/opt/backups/restic')
        self.restic_password = os.environ.get('RESTIC_PASSWORD', '')
        self.backup_paths = os.environ.get('BACKUP_PATHS', '/opt/stremio-stack')
        self.compose_base = os.environ.get('COMPOSE_DIR', '/opt/stremio-stack')

    def _run_restic(self, args, timeout=300):
        """Run a restic command with proper environment"""
        env = os.environ.copy()
        env['RESTIC_REPOSITORY'] = self.restic_repo
        env['RESTIC_PASSWORD'] = self.restic_password

        cmd = ['restic'] + args

        try:
            result = subprocess.run(
                cmd,
                capture_output=True,
                text=True,
                timeout=timeout,
                env=env
            )
            return {
                'success': result.returncode == 0,
                'stdout': result.stdout,
                'stderr': result.stderr,
                'returncode': result.returncode
            }
        except subprocess.TimeoutExpired:
            return {
                'success': False,
                'error': 'Command timed out',
                'returncode': -1
            }
        except Exception as e:
            return {
                'success': False,
                'error': str(e),
                'returncode': -1
            }

    def backup_stack(self, stack_name):
        """
        Create a backup for a specific stack
        Tags: stack:<stack_name>, timestamp
        """
        # Determine paths to backup
        stack_path = os.path.join(self.compose_base, stack_name)

        if not os.path.exists(stack_path):
            # Try the main compose directory
            stack_path = self.compose_base

        timestamp = datetime.utcnow().strftime('%Y%m%d-%H%M%S')
        tags = [f'stack:{stack_name}', f'timestamp:{timestamp}']

        # Build restic backup command
        args = ['backup', stack_path]
        for tag in tags:
            args.extend(['--tag', tag])

        result = self._run_restic(args, timeout=600)

        if result['success']:
            return {
                'success': True,
                'message': f'Backup created for {stack_name}',
                'tags': tags,
                'timestamp': timestamp,
                'output': result['stdout']
            }
        else:
            return {
                'success': False,
                'message': f'Backup failed for {stack_name}',
                'error': result.get('stderr', result.get('error', 'Unknown error'))
            }

    def restore_stack(self, stack_name, snapshot_id='latest'):
        """
        Restore a stack from backup
        WARNING: Stack should be stopped before restore
        """
        stack_path = os.path.join(self.compose_base, stack_name)

        if not os.path.exists(stack_path):
            stack_path = self.compose_base

        # If 'latest', find the most recent snapshot for this stack
        if snapshot_id == 'latest':
            snapshots = self.list_snapshots(stack_name)
            if not snapshots:
                return {
                    'success': False,
                    'message': f'No snapshots found for stack: {stack_name}'
                }
            snapshot_id = snapshots[0]['id']

        # Restore to the stack path
        args = ['restore', snapshot_id, '--target', stack_path]

        result = self._run_restic(args, timeout=600)

        if result['success']:
            return {
                'success': True,
                'message': f'Restored {stack_name} from snapshot {snapshot_id}',
                'snapshot_id': snapshot_id,
                'output': result['stdout']
            }
        else:
            return {
                'success': False,
                'message': f'Restore failed for {stack_name}',
                'error': result.get('stderr', result.get('error', 'Unknown error'))
            }

    def list_snapshots(self, stack_name=None):
        """List available snapshots, optionally filtered by stack"""
        args = ['snapshots', '--json']

        if stack_name:
            args.extend(['--tag', f'stack:{stack_name}'])

        result = self._run_restic(args)

        if not result['success']:
            return []

        try:
            snapshots = json.loads(result['stdout'])
            # Sort by time, newest first
            snapshots.sort(key=lambda x: x.get('time', ''), reverse=True)

            return [{
                'id': s.get('short_id', s.get('id', '')[:8]),
                'full_id': s.get('id', ''),
                'time': s.get('time', ''),
                'tags': s.get('tags', []),
                'paths': s.get('paths', []),
                'hostname': s.get('hostname', '')
            } for s in snapshots]
        except json.JSONDecodeError:
            return []

    def init_repository(self):
        """Initialize restic repository if not exists"""
        result = self._run_restic(['init'])
        return result

    def check_repository(self):
        """Check repository health"""
        result = self._run_restic(['check'])
        return {
            'healthy': result['success'],
            'output': result.get('stdout', '') + result.get('stderr', '')
        }

    def prune_old_snapshots(self, keep_last=10, keep_daily=7, keep_weekly=4, keep_monthly=6):
        """Remove old snapshots according to retention policy"""
        args = [
            'forget', '--prune',
            '--keep-last', str(keep_last),
            '--keep-daily', str(keep_daily),
            '--keep-weekly', str(keep_weekly),
            '--keep-monthly', str(keep_monthly)
        ]

        result = self._run_restic(args, timeout=600)
        return {
            'success': result['success'],
            'output': result.get('stdout', '') + result.get('stderr', '')
        }

    def get_repository_stats(self):
        """Get repository statistics"""
        result = self._run_restic(['stats', '--json'])

        if not result['success']:
            return {'error': 'Failed to get stats'}

        try:
            return json.loads(result['stdout'])
        except json.JSONDecodeError:
            return {'error': 'Failed to parse stats'}
