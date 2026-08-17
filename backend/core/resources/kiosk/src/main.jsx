import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import App from './App';
import './styles/global.css';

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter basename="/seller/kiosk">
      <App />
      <ToastContainer position="bottom-center" autoClose={3000} theme="colored" />
    </BrowserRouter>
  </React.StrictMode>
);
