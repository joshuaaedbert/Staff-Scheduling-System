import { useEffect, useMemo, useState } from "react";
import { getStaff, createStaff } from "./lib/api";
import { getShifts, createShift, assignShift, unassignShift } from "./lib/api";
import StaffList from "./components/StaffList";
import StaffForm from "./components/StaffForm";
import ShiftForm from "./components/ShiftForm";
import ShiftsList from "./components/ShiftsList";
import "./App.css";

function sortShifts(arr) {
  return [...arr].sort((a, b) => {
    const d = a.day.localeCompare(b.day);
    if (d !== 0) return d;
    const t = a.start_time.localeCompare(b.start_time);
    if (t !== 0) return t;
    return a.id - b.id;
  });
}

export default function App() {
  const [staff, setStaff] = useState([]);
  const [shifts, setShifts] = useState([]);
  const [loadingStaff, setLoadingStaff] = useState(true);
  const [loadingShifts, setLoadingShifts] = useState(true);
  const [creatingStaff, setCreatingStaff] = useState(false);
  const [creatingShift, setCreatingShift] = useState(false);
  const [error, setError] = useState("");
  const [dayFilter, setDayFilter] = useState("");

  // load staff
  useEffect(() => {
    (async () => {
      try {
        setLoadingStaff(true);
        const st = await getStaff();
        setStaff(st);
      } catch (err) {
        setError(err.message || "Failed to load staff");
      } finally {
        setLoadingStaff(false);
      }
    })();
  }, []);

  // load shifts (optionally filtered by day)
  useEffect(() => {
    (async () => {
      try {
        setLoadingShifts(true);
        const sh = await getShifts(dayFilter || undefined);
        setShifts(sh);
      } catch (err) {
        setError(err.message || "Failed to load shifts");
      } finally {
        setLoadingShifts(false);
      }
    })();
  }, [dayFilter]);

  async function handleCreateStaff(payload) {
    setCreatingStaff(true);
    try {
      const created = await createStaff(payload);
      setStaff((prev) => [created, ...prev]);
    } finally {
      setCreatingStaff(false);
    }
  }

  async function handleCreateShift(payload) {
    setCreatingShift(true);
    try {
      const created = await createShift(payload);
      setShifts((prev) => sortShifts([...prev, created]));
    } finally {
      setCreatingShift(false);
    }
  }

  async function handleAssign(shiftId, staffId) {
    const updated = await assignShift(shiftId, staffId);
    setShifts((prev) => prev.map((s) => (s.id === updated.id ? updated : s)));
  }

  async function handleUnassign(shiftId) {
    const updated = await unassignShift(shiftId);
    setShifts((prev) => prev.map((s) => (s.id === updated.id ? updated : s)));
  }

  const staffById = useMemo(() => Object.fromEntries(staff.map(s => [s.id, s])), [staff]);

  return (
    <div className="container">
      <header className="header">
        <h1>Staff Scheduler</h1>
        <p className="sub">Manage your team and shifts</p>
      </header>

      <main className="grid">
        <section className="left">
          <StaffForm onCreate={handleCreateStaff} busy={creatingStaff} />
          <div style={{ height: 16 }} />
          <ShiftForm onCreate={handleCreateShift} busy={creatingShift} />
        </section>

        <section className="right">
          <h2 className="h2">All Staff</h2>
          {loadingStaff ? (
            <div className="loading">Loading staff…</div>
          ) : error ? (
            <div className="error">{error}</div>
          ) : (
            <StaffList staff={staff} />
          )}

          <div style={{ height: 20 }} />

          <div className="row between">
            <h2 className="h2">Shifts</h2>
            <div className="filter">
              <label>
                <span>Filter by day</span>
                <input
                  type="date"
                  value={dayFilter}
                  onChange={(e) => setDayFilter(e.target.value)}
                />
              </label>
              {dayFilter && (
                <button className="btn outline" onClick={() => setDayFilter("")}>
                  Clear
                </button>
              )}
            </div>
          </div>

          {loadingShifts ? (
            <div className="loading">Loading shifts…</div>
          ) : error ? (
            <div className="error">{error}</div>
          ) : (
            <ShiftsList
              shifts={shifts}
              staff={staff}
              onAssign={handleAssign}
              onUnassign={handleUnassign}
            />
          )}
        </section>
      </main>
    </div>
  );
}
