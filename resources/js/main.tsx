import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import "../css/app.css";

const root = document.getElementById("app");

if (!root) throw new Error("App root was not found.");

createRoot(root).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
