import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import StaffForm from "./StaffForm";

describe("StaffForm", () => {
  it("submits valid data to onCreate", async () => {
    const onCreate = vi.fn().mockResolvedValue({});
    render(<StaffForm onCreate={onCreate} busy={false} />);

    fireEvent.change(screen.getByPlaceholderText(/Alice Nguyen/i), { target: { value: "Bob Lee" } });
    fireEvent.change(screen.getByDisplayValue("server"), { target: { value: "cook" } });
    fireEvent.change(screen.getByPlaceholderText(/306-555/i), { target: { value: "306-555-0000" } });

    fireEvent.click(screen.getByRole("button", { name: /add staff/i }));
    // allow promises to resolve
    await new Promise(r => setTimeout(r, 0));

    expect(onCreate).toHaveBeenCalledWith({ name: "Bob Lee", role: "cook", phone: "306-555-0000" });
  });

  it("shows validation error when name missing", () => {
    const onCreate = vi.fn();
    render(<StaffForm onCreate={onCreate} busy={false} />);
    fireEvent.click(screen.getByRole("button", { name: /add staff/i }));
    expect(screen.getByText(/name is required/i)).toBeInTheDocument();
  });
});
