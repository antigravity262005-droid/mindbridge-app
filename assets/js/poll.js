/**
 * MindBridge: Real-Time Live Polling System
 * Authenticated polling every 3 seconds with tab visibility detection
 * and client-side notifications for new requests or counselor updates.
 */

(function initLivePolling() {
  const POLL_INTERVAL = 3000; // 3 seconds
  let timerId = null;
  let isPolling = false;

  // State cache for detecting changes
  let lastSeenAdminRequestId = null;
  let lastSeenStudentUpdateId = null;

  async function fetchPollData() {
    if (isPolling) return;
    isPolling = true;

    try {
      // In XAMPP, path is /mindbridge/api/poll.php
      const baseUrl = window.location.pathname.startsWith('/mindbridge') ? '/mindbridge' : '';
      const response = await fetch(`${baseUrl}/api/poll.php`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        cache: 'no-cache'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();

      if (!data.success || !data.logged_in) {
        // User logged out or session expired
        stopPolling();
        return;
      }

      // Update pulse indicator status
      const indicatorText = document.getElementById('live-indicator-text');
      if (indicatorText) {
        indicatorText.textContent = 'Live · Synchronized';
      }

      // 1. ADMIN UPDATES
      if (data.role === 'administrator' && data.admin_data) {
        const adminData = data.admin_data;

        // Update Open Requests count in dashboard or header if present
        const openReqEls = document.querySelectorAll('#dash-admin-open-requests');
        openReqEls.forEach(el => {
          if (el.textContent.trim() !== String(adminData.open_requests)) {
            el.textContent = adminData.open_requests;
            el.classList.add('animate-pulse');
            setTimeout(() => el.classList.remove('animate-pulse'), 1000);
          }
        });

        // Update Registered Students count
        const totalStuEls = document.querySelectorAll('#dash-total-students');
        totalStuEls.forEach(el => {
          el.textContent = adminData.total_students;
        });

        // Check if a new student request arrived
        if (adminData.latest_request) {
          const req = adminData.latest_request;
          if (lastSeenAdminRequestId !== null && req.id > lastSeenAdminRequestId) {
            if (window.showToast) {
              window.showToast(`New ${req.status} request from ${req.student_name}: "${req.subject}"`, 'warning', 6000);
            }
          }
          lastSeenAdminRequestId = req.id;
        }
      }

      // 2. STUDENT UPDATES
      if (data.role === 'user' && data.student_data) {
        const studentData = data.student_data;

        // Check if counselor posted a new follow-up note
        if (studentData.latest_update) {
          const up = studentData.latest_update;
          if (lastSeenStudentUpdateId !== null && up.update_id > lastSeenStudentUpdateId) {
            if (window.showToast) {
              window.showToast(`Counselor update: Request #${up.request_id} is now "${up.status}". Note: ${up.update_note}`, 'success', 8000);
            }
          }
          lastSeenStudentUpdateId = up.update_id;
        }
      }

    } catch (err) {
      const indicatorText = document.getElementById('live-indicator-text');
      if (indicatorText) {
        indicatorText.textContent = 'Reconnecting...';
      }
    } finally {
      isPolling = false;
    }
  }

  function startPolling() {
    if (timerId !== null) return;
    // Immediate execution, then recurring
    fetchPollData();
    timerId = setInterval(fetchPollData, POLL_INTERVAL);
  }

  function stopPolling() {
    if (timerId !== null) {
      clearInterval(timerId);
      timerId = null;
    }
    const indicatorText = document.getElementById('live-indicator-text');
    if (indicatorText) {
      indicatorText.textContent = 'Paused (Tab Inactive)';
    }
  }

  // Handle Tab Visibility (Pause on background tab, resume on focus)
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopPolling();
    } else {
      startPolling();
    }
  });

  // Start polling on page load
  startPolling();
})();
