import { useMemo, useState } from "react";

function byDayAndTime(a, b) {
  const d = a.day.localeCompare(b.day);
  if (d !== 0) return d;
  const t = a.start_time.localeCompare(b.start_time);
  if (t !== 0) return t;
  return a.id - b.id;
}

export default function ShiftsList({ shifts, staff, onAssign, onUnassign }) {
  const [pending, setPending] = useState({}); // shift_id -> boolean
  const staffByRole = useMemo(() => {
    const map = { server: [], cook: [], manager: [] };
    (staff || []).forEach((s) => {
      if (!map[s.role]) map[s.role] = [];
      map[s.role].push(s);
    });
    return map;
  }, [staff]);

  const sortedShifts = useMemo(() => {
    return [...(shifts || [])].sort(byDayAndTime);
  }, [shifts]);

  async function handleAssign(shiftId, staffId) {
    if (!staffId) return;
    setPending((p) => ({ ...p, [shiftId]: true }));
    try {
      await onAssign(shiftId, staffId);
    } finally {
      setPending((p) => ({ ...p, [shiftId]: false }));
    }
  }

  async function handleUnassign(shiftId) {
    setPending((p) => ({ ...p, [shiftId]: true }));
    try {
      await onUnassign(shiftId);
    } finally {
      setPending((p) => ({ ...p, [shiftId]: false }));
    }
  }

  if (!sortedShifts.length) {
    return <div className="empty">No shifts yet. Create one on the left.</div>;
  }

  return (
    <div className="table card">
      <div className="thead">
        <div>Day</div>
        <div>Time</div>
        <div>Role</div>
        <div>Assigned</div>
        <div>Actions</div>
      </div>

      {sortedShifts.map((sh) => {
        const options = staffByRole[sh.role] || [];
        const disabled = !!pending[sh.id];

        return (
          <div key={sh.id} className="trow">
            <div className="col">{sh.day}</div>
            <div className="col">{sh.start_time}–{sh.end_time}</div>
            <div className="col"><span className="role">{sh.role}</span></div>
            <div className="col">
              {sh.staff_id ? (sh.staff_name || `#${sh.staff_id}`) : <em className="muted">Unassigned</em>}
            </div>
            <div className="col actions">
              {sh.staff_id ? (
                <button className="btn outline" disabled={disabled} onClick={() => handleUnassign(sh.id)}>
                  {disabled ? "..." : "Unassign"}
                </button>
              ) : (
                <AssignControl
                  disabled={disabled}
                  options={options}
                  onAssign={(sid) => handleAssign(sh.id, sid)}
                />
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
}

function AssignControl({ options, disabled, onAssign }) {
  const [sel, setSel] = useState("");
  return (
    <div className="row">
      <select
        value={sel}
        disabled={disabled || options.length === 0}
        onChange={(e) => setSel(e.target.value)}
      >
        <option value="">Pick staff…</option>
        {options.map((s) => (
          <option key={s.id} value={s.id}>
            {s.name} ({s.role})
          </option>
        ))}
      </select>
      <button
        className="btn"
        disabled={disabled || !sel}
        onClick={() => onAssign(Number(sel))}
      >
        Assign
      </button>
    </div>
  );
}
