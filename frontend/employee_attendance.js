(() => {
  const timeValueEl = document.getElementById('currentTimeValue');
  const meridiemEl = document.getElementById('currentMeridiem');
  const dateLabelEl = document.getElementById('currentDateLabel');
  const statusLabelEl = document.getElementById('shiftStatusLabel');
  const statusEl = document.getElementById('shiftStatus');
  const startedAtEl = document.getElementById('startedAt');
  const activeDurationEl = document.getElementById('activeDuration');
  const clockInBtn = document.getElementById('clockInBtn');
  const clockOutBtn = document.getElementById('clockOutBtn');
  const messageEl = document.getElementById('clockMessage');

  if (!timeValueEl || !clockInBtn || !clockOutBtn) return;

  const apiUrl = '../backend/employee_attendance_api.php';
  let latestAttendance = null;

  const formatTime = (dateObj) => {
    const hours = dateObj.getHours();
    const minutes = dateObj.getMinutes();
    const meridiem = hours >= 12 ? 'PM' : 'AM';
    const normalizedHours = ((hours + 11) % 12) + 1;
    return {
      time: `${String(normalizedHours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`,
      meridiem
    };
  };

  const formatTimeString = (timeString) => {
    if (!timeString) return '—';
    const [h, m] = timeString.split(':');
    const date = new Date();
    date.setHours(parseInt(h, 10), parseInt(m, 10), 0, 0);
    const formatted = formatTime(date);
    return `${formatted.time} ${formatted.meridiem}`;
  };

  const formatDuration = (seconds) => {
    if (!seconds || seconds <= 0) return '—';
    const totalMinutes = Math.floor(seconds / 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    if (hours > 0) {
      return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
  };

  const setMessage = (text, isError = false) => {
    if (!messageEl) return;
    messageEl.textContent = text || '';
    messageEl.classList.toggle('text-red-500', isError);
    messageEl.classList.toggle('text-[#9a734c]', !isError);
    messageEl.classList.toggle('dark:text-red-400', isError);
    messageEl.classList.toggle('dark:text-[#c5a484]', !isError);
  };

  const updateTime = () => {
    const now = new Date();
    const formatted = formatTime(now);
    timeValueEl.textContent = formatted.time;
    meridiemEl.textContent = formatted.meridiem;

    if (dateLabelEl) {
      dateLabelEl.textContent = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    }
  };

  const applyButtonState = (isClockedIn) => {
    clockInBtn.disabled = isClockedIn;
    clockOutBtn.disabled = !isClockedIn;

    clockInBtn.classList.toggle('opacity-60', isClockedIn);
    clockInBtn.classList.toggle('cursor-not-allowed', isClockedIn);
    clockOutBtn.classList.toggle('opacity-60', !isClockedIn);
    clockOutBtn.classList.toggle('cursor-not-allowed', !isClockedIn);

    if (statusEl && statusLabelEl) {
      if (isClockedIn) {
        statusEl.classList.add('bg-primary/10', 'text-primary');
        statusEl.classList.remove('bg-slate-200', 'text-slate-600', 'dark:bg-[#3d2e21]', 'dark:text-[#c5a484]');
        statusLabelEl.textContent = 'Shift In Progress';
      } else {
        statusEl.classList.remove('bg-primary/10', 'text-primary');
        statusEl.classList.add('bg-slate-200', 'text-slate-600', 'dark:bg-[#3d2e21]', 'dark:text-[#c5a484]');
        statusLabelEl.textContent = 'Not Clocked In';
      }
    }
  };

  const updateAttendanceUI = (data) => {
    latestAttendance = data?.attendance || null;
    const isClockedIn = Boolean(data?.is_clocked_in);

    startedAtEl.textContent = formatTimeString(latestAttendance?.check_in);
    activeDurationEl.textContent = formatDuration(data?.duration_seconds);

    applyButtonState(isClockedIn);
  };

  const fetchAttendance = async () => {
    try {
      const response = await fetch(apiUrl, { method: 'GET' });
      const data = await response.json();
      if (!response.ok || !data.success) {
        setMessage(data.message || 'Unable to load attendance status.', true);
        return;
      }
      setMessage('');
      updateAttendanceUI(data);
    } catch (error) {
      setMessage('Unable to load attendance status.', true);
    }
  };

  const postAction = async (action) => {
    setMessage('');
    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action })
      });
      const data = await response.json();
      if (!response.ok || !data.success) {
        setMessage(data.message || 'Unable to update attendance.', true);
        return;
      }
      setMessage(data.message || 'Attendance updated.');
      updateAttendanceUI(data);
    } catch (error) {
      setMessage('Unable to update attendance.', true);
    }
  };

  clockInBtn.addEventListener('click', () => {
    if (clockInBtn.disabled) return;
    postAction('clock_in');
  });

  clockOutBtn.addEventListener('click', () => {
    if (clockOutBtn.disabled) return;
    postAction('clock_out');
  });

  updateTime();
  setInterval(updateTime, 1000);
  fetchAttendance();
  setInterval(fetchAttendance, 60000);
})();
