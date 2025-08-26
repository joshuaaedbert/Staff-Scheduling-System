// frontend/src/App.jsx
import { useEffect, useState } from "react";
import { getStaff, createStaff } from "./lib/api";
import StaffList from "./components/StaffList";
import StaffForm from "./components/StaffForm";
import "./App.css";

export default function App() {
  const [staff, setStaff] = useState([]);
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    (async () => {
      try {
        const data = await getStaff();
        setStaff(data);
      } catch (err) {
        setError(err.message || "Failed to load staff");
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  async function handleCreate(payload) {
    setCreating(true);
    try {
      const created = await createStaff(payload);
      // Prepend so newest appears first (matches API order)
      setStaff((prev) => [created, ...prev]);
    } finally {
      setCreating(false);
    }
  }

  return (
    <div className="container">
      <header className="header">
        <h1>Staff Scheduler</h1>
        <p className="sub">Manage your team and shifts</p>
      </header>

      <main className="grid">
        <section className="left">
          <StaffForm onCreate={handleCreate} busy={creating} />
        </section>

        <section className="right">
          <h2 className="h2">All Staff</h2>
          {loading ? (
            <div className="loading">Loading...</div>
          ) : error ? (
            <div className="error">{error}</div>
          ) : (
            <StaffList staff={staff} />
          )}
        </section>
      </main>
    </div>
  );
}
