import './bootstrap';
import { createApp } from 'vue';

// Import Vue components
import RecordsDashboard from './components/RecordsDashboard.vue';
import RecordDetail from './components/RecordDetail.vue';
import SettingsPage from './components/SettingsPage.vue';

// Check which component to mount based on what's on the page
const recordsDashboardEl = document.getElementById('records-dashboard');
const recordDetailEl = document.getElementById('record-detail');
const settingsPageEl = document.getElementById('settings-page');

if (recordsDashboardEl) {
    // Mount RecordsDashboard component
    const dashboardApp = createApp(RecordsDashboard);
    dashboardApp.mount('#records-dashboard');
}

if (recordDetailEl) {
    // Mount RecordDetail component with recordId prop
    const recordId = recordDetailEl.dataset.recordId;
    const detailApp = createApp(RecordDetail, { recordId });
    detailApp.mount('#record-detail');
}

if (settingsPageEl) {
    // Mount SettingsPage component
    const settingsApp = createApp(SettingsPage);
    settingsApp.mount('#settings-page');
}
