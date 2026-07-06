# Admin feature recommendations (before implementing real logic)

1. Replace placeholder `use*` hooks with real API calls using a shared service layer.
2. Ensure `role:admin` middleware is the canonical authorization mechanism (avoid duplicate policy/middleware checks unless required).
3. Add export handling (CSV/XLSX/PDF) via a shared export abstraction.
4. Align table components props with the backend response shapes (data + pagination meta).

