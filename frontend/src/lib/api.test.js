import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { getStaff, createStaff } from "./api";

const API = "http://localhost:8000";

beforeEach(() => {
  vi.spyOn(globalThis, "fetch");
  import.meta.env.VITE_API_BASE = API;
});

afterEach(() => {
  vi.restoreAllMocks();
});

it("getStaff: returns list on 200", async () => {
  fetch.mockResolvedValueOnce(new Response(JSON.stringify([{ id: 1, name: "Alice", role: "server" }]), { status: 200 }));
  const res = await getStaff();
  expect(res[0].name).toBe("Alice");
  expect(fetch).toHaveBeenCalledWith(`${API}/index.php?path=staff`);
});

it("createStaff: throws on 400 with server error", async () => {
  fetch.mockResolvedValueOnce(new Response(JSON.stringify({ error: "Fields 'name' and 'role' are required" }), { status: 400, statusText: "Bad Request" }));
  await expect(createStaff({})).rejects.toThrow(/Fields 'name' and 'role' are required/);
});
