// frontend/src/lib/api.js

// --- Staff API ---
const API_BASE = import.meta.env.VITE_API_BASE ?? "http://localhost:8000";

async function handle(res) {
  if (!res.ok) {
    let msg = "Request failed";
    try { msg = (await res.json()).error ?? msg; } catch {}
    throw new Error(`${res.status} ${res.statusText}: ${msg}`);
  }
  return res.json();
}

export async function getStaff() {
  const res = await fetch(`${API_BASE}/index.php?path=staff`);
  return handle(res);
}

export async function createStaff(payload) {
  const res = await fetch(`${API_BASE}/index.php?path=staff`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  return handle(res);
}


// --- Shifts API ---
export async function getShifts(day) {
  const qs = day ? `&day=${encodeURIComponent(day)}` : "";
  const res = await fetch(`${API_BASE}/index.php?path=shifts${qs}`);
  return handle(res);
}

export async function createShift(payload) {
  const res = await fetch(`${API_BASE}/index.php?path=shifts`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  return handle(res);
}

export async function assignShift(shift_id, staff_id) {
  const res = await fetch(`${API_BASE}/index.php?path=shifts&action=assign`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ shift_id, staff_id }),
  });
  return handle(res);
}

export async function unassignShift(shift_id) {
  const res = await fetch(`${API_BASE}/index.php?path=shifts&action=unassign`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ shift_id }),
  });
  return handle(res);
}
