// frontend/src/components/StaffForm.jsx
import { useState } from "react";

const ROLES = ["server", "cook", "manager"];

export default function StaffForm({ onCreate, busy }) {
  const [name, setName] = useState("");
  const [role, setRole] = useState(ROLES[0]);
  const [phone, setPhone] = useState("");
  const [error, setError] = useState("");

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");

    if (!name.trim()) {
      setError("Name is required");
      return;
    }
    if (!ROLES.includes(role)) {
      setError("Invalid role");
      return;
    }

    try {
      await onCreate({ name: name.trim(), role, phone: phone.trim() || null });
      setName("");
      setRole(ROLES[0]);
      setPhone("");
    } catch (err) {
      setError(err.message || "Failed to create staff");
    }
  }

  return (
    <form className="form" onSubmit={handleSubmit}>
      <h2 className="h2">Add Staff</h2>

      <label>
        <span>Name</span>
        <input
          placeholder="e.g., Alice Nguyen"
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
      </label>

      <label>
        <span>Role</span>
        <select value={role} onChange={(e) => setRole(e.target.value)}>
          {ROLES.map((r) => (
            <option key={r} value={r}>{r}</option>
          ))}
        </select>
      </label>

      <label>
        <span>Phone (optional)</span>
        <input
          placeholder="306-555-1234"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
        />
      </label>

      {error ? <div className="error">{error}</div> : null}

      <button className="btn" type="submit" disabled={busy}>
        {busy ? "Saving..." : "Add Staff"}
      </button>
    </form>
  );
}
