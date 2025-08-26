import { useState } from "react";

const ROLES = ["server", "cook", "manager"];

export default function ShiftForm({ onCreate, busy }) {
  const [day, setDay] = useState("");
  const [start, setStart] = useState("");
  const [end, setEnd] = useState("");
  const [role, setRole] = useState(ROLES[0]);
  const [error, setError] = useState("");

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");

    if (!day || !start || !end) {
      setError("Day, start time and end time are required.");
      return;
    }
    if (start >= end) {
      setError("Start time must be earlier than end time.");
      return;
    }

    try {
      await onCreate({
        day,
        start_time: start,
        end_time: end,
        role,
      });
      // reset
      setStart("");
      setEnd("");
      // keep day and role to speed up repeated entries
    } catch (err) {
      setError(err.message || "Failed to create shift");
    }
  }

  return (
    <form className="form" onSubmit={handleSubmit}>
      <h2 className="h2">Create Shift</h2>

      <label>
        <span>Day</span>
        <input type="date" value={day} onChange={(e) => setDay(e.target.value)} />
      </label>

      <div className="two">
        <label>
          <span>Start</span>
          <input type="time" value={start} onChange={(e) => setStart(e.target.value)} />
        </label>
        <label>
          <span>End</span>
          <input type="time" value={end} onChange={(e) => setEnd(e.target.value)} />
        </label>
      </div>

      <label>
        <span>Role</span>
        <select value={role} onChange={(e) => setRole(e.target.value)}>
          {ROLES.map((r) => (
            <option key={r} value={r}>{r}</option>
          ))}
        </select>
      </label>

      {error ? <div className="error">{error}</div> : null}

      <button className="btn" type="submit" disabled={busy}>
        {busy ? "Creating..." : "Create Shift"}
      </button>
    </form>
  );
}
