// frontend/src/components/StaffList.jsx
export default function StaffList({ staff }) {
  if (!staff?.length) {
    return (
      <div className="empty">
        <p>No staff yet. Add your first team member 👇</p>
      </div>
    );
  }

  return (
    <ul className="staff-list">
      {staff.map((s) => (
        <li key={s.id} className="card">
          <div className="title">
            <strong>{s.name}</strong>
            <span className="role">{s.role}</span>
          </div>
          {s.phone ? <div className="meta">📞 {s.phone}</div> : null}
        </li>
      ))}
    </ul>
  );
}
