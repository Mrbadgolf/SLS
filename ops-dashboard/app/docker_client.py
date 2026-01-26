"""
Docker Client - Container management via Docker API
"""

import docker
import os
import subprocess
from datetime import datetime


class DockerClient:
    def __init__(self):
        self.socket_path = os.environ.get('DOCKER_SOCKET', '/var/run/docker.sock')
        self.client = None
        self._connect()

    def _connect(self):
        """Establish connection to Docker daemon"""
        try:
            self.client = docker.DockerClient(base_url=f'unix://{self.socket_path}')
            self.client.ping()
        except Exception as e:
            print(f"Failed to connect to Docker: {e}")
            self.client = None

    def is_connected(self):
        """Check if connected to Docker daemon"""
        if not self.client:
            return False
        try:
            self.client.ping()
            return True
        except:
            return False

    def list_containers(self, all_containers=True):
        """List all containers with their status and basic info"""
        if not self.client:
            return []

        containers = []
        for container in self.client.containers.list(all=all_containers):
            # Get container info
            info = container.attrs

            # Extract network info
            networks = list(info.get('NetworkSettings', {}).get('Networks', {}).keys())

            # Determine stack/project name from labels
            labels = info.get('Config', {}).get('Labels', {})
            stack_name = labels.get('com.docker.compose.project', 'standalone')
            service_name = labels.get('com.docker.compose.service', container.name)

            containers.append({
                'id': container.short_id,
                'full_id': container.id,
                'name': container.name,
                'service': service_name,
                'stack': stack_name,
                'image': container.image.tags[0] if container.image.tags else container.image.short_id,
                'status': container.status,
                'state': info.get('State', {}),
                'networks': networks,
                'created': info.get('Created', ''),
                'ports': self._format_ports(info.get('NetworkSettings', {}).get('Ports', {})),
                'labels': labels
            })

        # Sort by stack then by name
        containers.sort(key=lambda x: (x['stack'], x['name']))
        return containers

    def _format_ports(self, ports):
        """Format port mappings for display"""
        formatted = []
        for container_port, host_bindings in ports.items():
            if host_bindings:
                for binding in host_bindings:
                    formatted.append(f"{binding.get('HostPort', '?')}:{container_port}")
            else:
                formatted.append(container_port)
        return formatted

    def container_action(self, container_id, action):
        """Perform action on a container"""
        if not self.client:
            raise Exception("Not connected to Docker")

        container = self.client.containers.get(container_id)

        if action == 'start':
            container.start()
            return {'message': f'Container {container.name} started'}
        elif action == 'stop':
            container.stop(timeout=30)
            return {'message': f'Container {container.name} stopped'}
        elif action == 'restart':
            container.restart(timeout=30)
            return {'message': f'Container {container.name} restarted'}
        else:
            raise ValueError(f'Unknown action: {action}')

    def get_container_stats(self, container_id):
        """Get real-time stats for a container"""
        if not self.client:
            raise Exception("Not connected to Docker")

        container = self.client.containers.get(container_id)

        if container.status != 'running':
            return {
                'status': container.status,
                'cpu_percent': 0,
                'memory_usage': 0,
                'memory_limit': 0,
                'memory_percent': 0,
                'network_rx': 0,
                'network_tx': 0
            }

        # Get one-shot stats (stream=False)
        stats = container.stats(stream=False)

        # Calculate CPU percentage
        cpu_delta = stats['cpu_stats']['cpu_usage']['total_usage'] - \
                    stats['precpu_stats']['cpu_usage']['total_usage']
        system_delta = stats['cpu_stats']['system_cpu_usage'] - \
                       stats['precpu_stats']['system_cpu_usage']
        cpu_count = stats['cpu_stats'].get('online_cpus', 1)

        cpu_percent = 0.0
        if system_delta > 0:
            cpu_percent = (cpu_delta / system_delta) * cpu_count * 100.0

        # Memory stats
        memory_usage = stats['memory_stats'].get('usage', 0)
        memory_limit = stats['memory_stats'].get('limit', 1)
        memory_percent = (memory_usage / memory_limit) * 100 if memory_limit > 0 else 0

        # Network stats
        networks = stats.get('networks', {})
        network_rx = sum(net.get('rx_bytes', 0) for net in networks.values())
        network_tx = sum(net.get('tx_bytes', 0) for net in networks.values())

        return {
            'status': container.status,
            'cpu_percent': round(cpu_percent, 2),
            'memory_usage': memory_usage,
            'memory_limit': memory_limit,
            'memory_percent': round(memory_percent, 2),
            'network_rx': network_rx,
            'network_tx': network_tx,
            'pids': stats.get('pids_stats', {}).get('current', 0)
        }

    def get_all_container_stats(self):
        """Get stats for all running containers"""
        if not self.client:
            return {}

        stats = {}
        for container in self.client.containers.list():
            try:
                stats[container.short_id] = self.get_container_stats(container.short_id)
            except Exception as e:
                stats[container.short_id] = {'error': str(e)}
        return stats

    def update_stack(self, stack_name, backup_first=True):
        """
        Update a Docker Compose stack
        This is typically handled by OliveTin, but we provide the capability
        """
        compose_dir = os.environ.get('COMPOSE_DIR', '/opt/stremio-stack')
        compose_file = os.path.join(compose_dir, stack_name, 'docker-compose.yml')

        if not os.path.exists(compose_file):
            # Try alternative location
            compose_file = os.path.join(compose_dir, 'docker-compose.yml')
            if not os.path.exists(compose_file):
                raise Exception(f"Compose file not found for stack: {stack_name}")

        results = []

        # Pull latest images
        pull_cmd = ['docker', 'compose', '-f', compose_file, 'pull']
        pull_result = subprocess.run(pull_cmd, capture_output=True, text=True)
        results.append({
            'step': 'pull',
            'success': pull_result.returncode == 0,
            'output': pull_result.stdout + pull_result.stderr
        })

        if pull_result.returncode != 0:
            return {'success': False, 'results': results}

        # Recreate containers
        up_cmd = ['docker', 'compose', '-f', compose_file, 'up', '-d', '--remove-orphans']
        up_result = subprocess.run(up_cmd, capture_output=True, text=True)
        results.append({
            'step': 'up',
            'success': up_result.returncode == 0,
            'output': up_result.stdout + up_result.stderr
        })

        return {
            'success': up_result.returncode == 0,
            'results': results,
            'timestamp': datetime.utcnow().isoformat()
        }

    def get_container_logs(self, container_id, tail=100):
        """Get recent logs from a container"""
        if not self.client:
            raise Exception("Not connected to Docker")

        container = self.client.containers.get(container_id)
        logs = container.logs(tail=tail, timestamps=True).decode('utf-8')
        return logs.split('\n')
