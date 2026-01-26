/**
 * Operations Dashboard - JavaScript
 * Handles all frontend interactions and API calls
 */

// State
let containers = [];
let systemStats = {};
let streamingActivity = {};
let snapshots = [];
let selectedContainer = null;
let refreshInterval = null;

// Configuration
const REFRESH_INTERVAL = 5000; // 5 seconds
const API_BASE = '';

// Utility Functions
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
}

function formatUptime(seconds) {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (days > 0) return `${days}d ${hours}h ${minutes}m`;
    if (hours > 0) return `${hours}h ${minutes}m`;
    return `${minutes}m`;
}

function formatTime(isoString) {
    if (!isoString) return '--';
    const date = new Date(isoString);
    return date.toLocaleTimeString();
}

function formatDate(isoString) {
    if (!isoString) return '--';
    const date = new Date(isoString);
    return date.toLocaleString();
}

// Toast Notifications
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// API Calls
async function fetchAPI(endpoint, options = {}) {
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error(`API Error (${endpoint}):`, error);
        throw error;
    }
}

// Connection Status
function updateConnectionStatus(connected, message = '') {
    const status = document.getElementById('connection-status');
    const text = status.querySelector('.status-text');

    status.className = 'connection-status ' + (connected ? 'connected' : 'error');
    text.textContent = message || (connected ? 'Connected' : 'Disconnected');
}

// System Stats
async function fetchSystemStats() {
    try {
        const data = await fetchAPI('/api/system/stats');
        if (data.success) {
            systemStats = data.stats;
            renderSystemStats();
            updateConnectionStatus(true);
        }
    } catch (error) {
        updateConnectionStatus(false, 'Connection Error');
    }
}

function renderSystemStats() {
    const stats = systemStats;

    // CPU
    const cpuPercent = stats.cpu?.percent || 0;
    document.getElementById('cpu-percent').textContent = cpuPercent.toFixed(1) + '%';
    document.getElementById('cpu-bar').style.width = cpuPercent + '%';
    document.getElementById('cpu-details').innerHTML = `
        <span>Cores: ${stats.cpu?.cores_logical || '--'}</span>
        <span>Load: ${stats.load?.['1min'] || '--'}</span>
    `;

    // Memory
    const memPercent = stats.memory?.percent || 0;
    document.getElementById('memory-percent').textContent = memPercent.toFixed(1) + '%';
    document.getElementById('memory-bar').style.width = memPercent + '%';
    document.getElementById('memory-details').innerHTML = `
        <span>Used: ${formatBytes(stats.memory?.used || 0)}</span>
        <span>Total: ${formatBytes(stats.memory?.total || 0)}</span>
    `;

    // Disk
    const diskStats = stats.disk?.['/'] || stats.disk?.[Object.keys(stats.disk || {})[0]] || {};
    const diskPercent = diskStats.percent || 0;
    document.getElementById('disk-percent').textContent = diskPercent.toFixed(1) + '%';
    document.getElementById('disk-bar').style.width = diskPercent + '%';
    document.getElementById('disk-details').innerHTML = `
        <span>Used: ${formatBytes(diskStats.used || 0)}</span>
        <span>Free: ${formatBytes(diskStats.free || 0)}</span>
    `;

    // Network
    const netStats = stats.network?.total || {};
    document.getElementById('network-stats').textContent = formatBytes(netStats.bytes_recv || 0) + '/s';
    document.getElementById('network-details').innerHTML = `
        <span>&#8595; ${formatBytes(netStats.bytes_recv || 0)}</span>
        <span>&#8593; ${formatBytes(netStats.bytes_sent || 0)}</span>
    `;

    // Uptime
    const uptimeEl = document.getElementById('uptime');
    if (stats.uptime) {
        uptimeEl.textContent = 'Uptime: ' + formatUptime(stats.uptime.seconds);
    }

    // Last refresh
    document.getElementById('last-refresh').textContent = 'Last refresh: ' + new Date().toLocaleTimeString();
}

// Containers
async function fetchContainers() {
    try {
        const data = await fetchAPI('/api/containers');
        if (data.success) {
            containers = data.containers;
            renderContainers();
            updateStackFilter();
        }
    } catch (error) {
        console.error('Failed to fetch containers:', error);
    }
}

function renderContainers() {
    const grid = document.getElementById('containers-grid');
    const filter = document.getElementById('stack-filter').value;

    const filtered = filter === 'all'
        ? containers
        : containers.filter(c => c.stack === filter);

    if (filtered.length === 0) {
        grid.innerHTML = '<div class="empty-state">No containers found</div>';
        return;
    }

    grid.innerHTML = filtered.map(container => `
        <div class="container-card ${container.status}" onclick="openContainerModal('${container.id}')">
            <div class="container-header">
                <span class="container-name">${container.name}</span>
                <span class="container-status ${container.status}">${container.status}</span>
            </div>
            <div class="container-image">${container.image}</div>
            <span class="container-stack">${container.stack}</span>
            <div class="container-stats" id="container-stats-${container.id}">
                <div class="container-stat">
                    <span>CPU:</span>
                    <span class="container-stat-value">--</span>
                </div>
                <div class="container-stat">
                    <span>Memory:</span>
                    <span class="container-stat-value">--</span>
                </div>
            </div>
            <div class="container-actions">
                <button class="btn btn-success btn-sm" onclick="event.stopPropagation(); quickAction('${container.id}', 'start')" ${container.status === 'running' ? 'disabled' : ''}>
                    Start
                </button>
                <button class="btn btn-warning btn-sm" onclick="event.stopPropagation(); quickAction('${container.id}', 'restart')">
                    Restart
                </button>
                <button class="btn btn-danger btn-sm" onclick="event.stopPropagation(); quickAction('${container.id}', 'stop')" ${container.status !== 'running' ? 'disabled' : ''}>
                    Stop
                </button>
            </div>
        </div>
    `).join('');

    // Fetch stats for running containers
    filtered.filter(c => c.status === 'running').forEach(c => {
        fetchContainerStats(c.id);
    });
}

async function fetchContainerStats(containerId) {
    try {
        const data = await fetchAPI(`/api/container/${containerId}/stats`);
        if (data.success) {
            const statsEl = document.getElementById(`container-stats-${containerId}`);
            if (statsEl) {
                statsEl.innerHTML = `
                    <div class="container-stat">
                        <span>CPU:</span>
                        <span class="container-stat-value">${data.stats.cpu_percent.toFixed(1)}%</span>
                    </div>
                    <div class="container-stat">
                        <span>Memory:</span>
                        <span class="container-stat-value">${formatBytes(data.stats.memory_usage)}</span>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error(`Failed to fetch stats for ${containerId}:`, error);
    }
}

function updateStackFilter() {
    const filter = document.getElementById('stack-filter');
    const stacks = [...new Set(containers.map(c => c.stack))];
    const currentValue = filter.value;

    filter.innerHTML = '<option value="all">All Stacks</option>' +
        stacks.map(s => `<option value="${s}" ${s === currentValue ? 'selected' : ''}>${s}</option>`).join('');
}

function filterContainers() {
    renderContainers();
}

function refreshContainers() {
    fetchContainers();
    showToast('Refreshing containers...', 'info');
}

async function quickAction(containerId, action) {
    try {
        const data = await fetchAPI(`/api/container/${containerId}/action`, {
            method: 'POST',
            body: JSON.stringify({ action })
        });

        if (data.success) {
            showToast(data.result.message, 'success');
            setTimeout(fetchContainers, 1000);
        } else {
            showToast(data.error || 'Action failed', 'error');
        }
    } catch (error) {
        showToast('Failed to perform action', 'error');
    }
}

// Container Modal
function openContainerModal(containerId) {
    selectedContainer = containers.find(c => c.id === containerId);
    if (!selectedContainer) return;

    document.getElementById('modal-container-name').textContent = selectedContainer.name;

    // Fetch and display stats
    fetchContainerStats(containerId).then(() => {
        const statsEl = document.getElementById('modal-stats');
        statsEl.innerHTML = `
            <div class="modal-stat">
                <div class="modal-stat-label">Status</div>
                <div class="modal-stat-value">${selectedContainer.status}</div>
            </div>
            <div class="modal-stat">
                <div class="modal-stat-label">Image</div>
                <div class="modal-stat-value" style="font-size: 0.875rem; word-break: break-all;">${selectedContainer.image}</div>
            </div>
            <div class="modal-stat">
                <div class="modal-stat-label">Stack</div>
                <div class="modal-stat-value">${selectedContainer.stack}</div>
            </div>
            <div class="modal-stat">
                <div class="modal-stat-label">Networks</div>
                <div class="modal-stat-value">${selectedContainer.networks.join(', ')}</div>
            </div>
        `;
    });

    showModal('container-modal');
}

async function containerAction(action) {
    if (!selectedContainer) return;

    try {
        const data = await fetchAPI(`/api/container/${selectedContainer.id}/action`, {
            method: 'POST',
            body: JSON.stringify({ action })
        });

        if (data.success) {
            showToast(data.result.message, 'success');
            closeModal();
            setTimeout(fetchContainers, 1000);
        } else {
            showToast(data.error || 'Action failed', 'error');
        }
    } catch (error) {
        showToast('Failed to perform action', 'error');
    }
}

// Streaming Activity
async function fetchStreamingActivity() {
    try {
        const data = await fetchAPI('/api/streaming/active');
        if (data.success) {
            streamingActivity = data.activity;
            renderStreamingActivity();
        }
    } catch (error) {
        console.error('Failed to fetch streaming activity:', error);
    }
}

function renderStreamingActivity() {
    const activity = streamingActivity;

    // Update badge
    document.getElementById('active-streams').textContent = activity.active_count || 0;

    // Update details
    document.getElementById('streaming-viewers').textContent = activity.active_count || 0;
    document.getElementById('streaming-requests').textContent = activity.requests_count || 0;
    document.getElementById('streaming-updated').textContent = formatTime(activity.timestamp);

    // Update endpoints
    const endpointsEl = document.getElementById('streaming-endpoints');
    const endpoints = activity.by_endpoint || {};

    if (Object.keys(endpoints).length === 0) {
        endpointsEl.innerHTML = '<span class="empty-state">No endpoint activity</span>';
    } else {
        endpointsEl.innerHTML = Object.entries(endpoints).map(([endpoint, count]) => `
            <div class="endpoint-tag">
                <span>${endpoint}</span>
                <span class="count">${count}</span>
            </div>
        `).join('');
    }
}

// Backups & Snapshots
async function fetchSnapshots() {
    try {
        // Fetch snapshots for the main stack
        const data = await fetchAPI('/api/backup/stremio/snapshots');
        if (data.success) {
            snapshots = data.snapshots;
            renderSnapshots();
        }
    } catch (error) {
        console.error('Failed to fetch snapshots:', error);
        document.getElementById('snapshots-list').innerHTML =
            '<div class="empty-state">Could not load snapshots. Restic may not be configured.</div>';
    }
}

function renderSnapshots() {
    const list = document.getElementById('snapshots-list');

    if (snapshots.length === 0) {
        list.innerHTML = '<div class="empty-state">No snapshots found</div>';
        return;
    }

    list.innerHTML = snapshots.slice(0, 10).map(snapshot => `
        <div class="snapshot-item">
            <div class="snapshot-info">
                <div class="snapshot-id">${snapshot.id}</div>
                <div class="snapshot-time">${formatDate(snapshot.time)}</div>
                <div class="snapshot-tags">
                    ${snapshot.tags.map(t => `<span class="snapshot-tag">${t}</span>`).join('')}
                </div>
            </div>
            <div class="snapshot-actions">
                <button class="btn btn-secondary" onclick="showRestoreModal('${snapshot.id}', '${snapshot.tags.find(t => t.startsWith('stack:'))?.split(':')[1] || 'stremio'}')">
                    Restore
                </button>
            </div>
        </div>
    `).join('');
}

function showBackupModal() {
    // Populate stack options
    const stacks = [...new Set(containers.map(c => c.stack))];
    const select = document.getElementById('backup-stack');
    select.innerHTML = stacks.map(s => `<option value="${s}">${s}</option>`).join('');

    showModal('backup-modal');
}

async function createBackup() {
    const stackName = document.getElementById('backup-stack').value;

    try {
        showToast(`Creating backup for ${stackName}...`, 'info');
        closeModal();

        const data = await fetchAPI(`/api/backup/${stackName}`, {
            method: 'POST'
        });

        if (data.success) {
            showToast(`Backup created successfully!`, 'success');
            fetchSnapshots();
        } else {
            showToast(data.error || 'Backup failed', 'error');
        }
    } catch (error) {
        showToast('Failed to create backup', 'error');
    }
}

function showRestoreModal(snapshotId, stackName) {
    document.getElementById('restore-snapshot-id').value = snapshotId;
    document.getElementById('restore-stack-name').value = stackName;
    showModal('restore-modal');
}

async function performRestore() {
    const snapshotId = document.getElementById('restore-snapshot-id').value;
    const stackName = document.getElementById('restore-stack-name').value;

    try {
        showToast(`Restoring ${stackName} from ${snapshotId}...`, 'warning');
        closeModal();

        const data = await fetchAPI(`/api/restore/${stackName}`, {
            method: 'POST',
            body: JSON.stringify({ snapshot_id: snapshotId })
        });

        if (data.success) {
            showToast(`Restore completed!`, 'success');
            fetchContainers();
        } else {
            showToast(data.error || 'Restore failed', 'error');
        }
    } catch (error) {
        showToast('Failed to restore', 'error');
    }
}

// Modal Management
function showModal(modalId) {
    document.getElementById('modal-overlay').classList.add('active');
    document.getElementById(modalId).classList.add('active');
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('active');
    document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
    selectedContainer = null;
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal();
    }
    if (e.key === 'r' && e.ctrlKey) {
        e.preventDefault();
        refreshContainers();
    }
});

// Initialize
async function init() {
    console.log('Operations Dashboard initializing...');

    // Initial data fetch
    await Promise.all([
        fetchSystemStats(),
        fetchContainers(),
        fetchStreamingActivity(),
        fetchSnapshots()
    ]);

    // Set up auto-refresh
    refreshInterval = setInterval(() => {
        fetchSystemStats();
        fetchStreamingActivity();

        // Refresh container stats for running containers
        containers.filter(c => c.status === 'running').forEach(c => {
            fetchContainerStats(c.id);
        });
    }, REFRESH_INTERVAL);

    // Less frequent container list refresh
    setInterval(fetchContainers, 30000);

    console.log('Dashboard initialized');
}

// Start when DOM is ready
document.addEventListener('DOMContentLoaded', init);

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
