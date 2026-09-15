import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import AuthPages from "./components/AuthPages";
import "../css/app.css";

const root = document.getElementById("app");

if (!root) throw new Error("App root was not found.");

createRoot(root).render(
  <StrictMode>
    {["/verify-email", "/forgot-password", "/reset-password", "/account"].includes(window.location.pathname)
      ? <AuthPages /> : <App />}
  </StrictMode>,
);
