import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import ShiftsList from "./ShiftsList";

const staff = [
  { id: 1, name: "Alice", role: "server" },
  { id: 2, name: "Bob", role: "cook" },
];
const shifts = [
  { id: 10, day: "2025-09-01", start_time: "09:00", end_time: "17:00", role: "server", staff_id: null },
];

describe("ShiftsList", () => {
  it("shows only matching role options and assigns", async () => {
    const onAssign = vi.fn().mockResolvedValue({});
    const onUnassign = vi.fn();

    render(<ShiftsList shifts={shifts} staff={staff} onAssign={onAssign} onUnassign={onUnassign} />);

    // Only Alice (server) should be in dropdown
    const select = screen.getByRole("combobox");
    const options = Array.from(select.querySelectorAll("option")).map(o => o.textContent);
    expect(options.some(t => /Alice/.test(t))).toBe(true);
    expect(options.some(t => /Bob/.test(t))).toBe(false);

    // Select Alice and click Assign
    fireEvent.change(select, { target: { value: "1" } });
    fireEvent.click(screen.getByRole("button", { name: /assign/i }));

    await new Promise(r => setTimeout(r, 0));
    expect(onAssign).toHaveBeenCalledWith(10, 1);
  });
});
