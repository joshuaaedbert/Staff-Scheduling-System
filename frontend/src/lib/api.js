// frontend/src/lib/api.js
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
