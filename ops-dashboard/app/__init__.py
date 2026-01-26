"""
Operations Dashboard - Main Flask Application
A self-hosted operations dashboard for Docker-based streaming stack
"""

import os
from flask import Flask, render_template, jsonify, request
from .docker_client import DockerClient
from .stats_collector import StatsCollector
from .streaming_tracker import StreamingTracker
from .backup_manager import BackupManager

app = Flask(__name__,
            template_folder='../templates',
            static_folder='../static')

# Initialize components
docker_client = DockerClient()
stats_collector = StatsCollector()
streaming_tracker = StreamingTracker()
backup_manager = BackupManager()

# Configuration
MANAGED_STACKS = os.environ.get('MANAGED_STACKS', 'stremio').split(',')
OLIVETIN_URL = os.environ.get('OLIVETIN_URL', 'https://olivetin.geaux-tv.com')
NETDATA_URL = os.environ.get('NETDATA_URL', 'https://netdata.geaux-tv.com')
PORTAINER_URL = os.environ.get('PORTAINER_URL', 'https://portainer.geaux-tv.com')


@app.route('/')
def dashboard():
    """Render the main dashboard page"""
    return render_template('dashboard.html',
                         olivetin_url=OLIVETIN_URL,
                         netdata_url=NETDATA_URL,
                         portainer_url=PORTAINER_URL)


@app.route('/api/containers')
def get_containers():
    """Get list of all containers with their status"""
    try:
        containers = docker_client.list_containers()
        return jsonify({'success': True, 'containers': containers})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/container/<container_id>/action', methods=['POST'])
def container_action(container_id):
    """Perform action on a container (start/stop/restart)"""
    action = request.json.get('action')
    if action not in ['start', 'stop', 'restart']:
        return jsonify({'success': False, 'error': 'Invalid action'}), 400

    try:
        result = docker_client.container_action(container_id, action)
        return jsonify({'success': True, 'result': result})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/container/<container_id>/stats')
def container_stats(container_id):
    """Get stats for a specific container"""
    try:
        stats = docker_client.get_container_stats(container_id)
        return jsonify({'success': True, 'stats': stats})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/system/stats')
def system_stats():
    """Get system-wide stats (CPU, RAM, disk, network)"""
    try:
        stats = stats_collector.get_system_stats()
        return jsonify({'success': True, 'stats': stats})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/streaming/active')
def streaming_active():
    """Get current streaming activity count"""
    try:
        activity = streaming_tracker.get_active_streams()
        return jsonify({'success': True, 'activity': activity})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/backup/<stack_name>', methods=['POST'])
def backup_stack(stack_name):
    """Trigger backup for a stack"""
    try:
        result = backup_manager.backup_stack(stack_name)
        return jsonify({'success': True, 'result': result})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/restore/<stack_name>', methods=['POST'])
def restore_stack(stack_name):
    """Trigger restore for a stack"""
    snapshot_id = request.json.get('snapshot_id', 'latest')
    try:
        result = backup_manager.restore_stack(stack_name, snapshot_id)
        return jsonify({'success': True, 'result': result})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/backup/<stack_name>/snapshots')
def list_snapshots(stack_name):
    """List available snapshots for a stack"""
    try:
        snapshots = backup_manager.list_snapshots(stack_name)
        return jsonify({'success': True, 'snapshots': snapshots})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/stack/<stack_name>/update', methods=['POST'])
def update_stack(stack_name):
    """Update a stack (pull + recreate)"""
    backup_first = request.json.get('backup_first', True)
    try:
        result = docker_client.update_stack(stack_name, backup_first)
        return jsonify({'success': True, 'result': result})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/health')
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'docker_connected': docker_client.is_connected(),
        'version': '1.0.0'
    })


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080, debug=False)
