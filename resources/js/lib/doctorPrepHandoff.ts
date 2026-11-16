/**
 * Hands the report's doctor topics to the doctor-visit page (M07-FR-06, M10-FR-06).
 *
 * Lives only in this tab's JavaScript memory and survives Inertia page visits; a reload or closing the tab
 * clears it. Nothing is written to storage or sent to the server.
 */
let pending: string[] = [];

export function setDoctorPrepHandoff(topics: string[]): void {
    pending = [...topics];
}

/** Reads the topics without clearing them (safe in a state initializer, which may run twice). */
export function peekDoctorPrepHandoff(): string[] {
    return [...pending];
}

export function clearDoctorPrepHandoff(): void {
    pending = [];
}
