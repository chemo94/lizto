import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuthStore } from './store/authStore';
import PinLogin from './pages/PinLogin';
import OrderType from './pages/OrderType';
import TableSelect from './pages/TableSelect';
import AddressForm from './pages/AddressForm';
import Menu from './pages/Menu';
import Cart from './pages/Cart';
import Confirmed from './pages/Confirmed';
import ActiveOrders from './pages/ActiveOrders';

function ProtectedRoute({ children }) {
  const token = useAuthStore((s) => s.token);
  if (!token) return <Navigate to="/login" replace />;
  return children;
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<PinLogin />} />
      <Route path="/" element={<ProtectedRoute><OrderType /></ProtectedRoute>} />
      <Route path="/tables" element={<ProtectedRoute><TableSelect /></ProtectedRoute>} />
      <Route path="/address" element={<ProtectedRoute><AddressForm /></ProtectedRoute>} />
      <Route path="/menu" element={<ProtectedRoute><Menu /></ProtectedRoute>} />
      <Route path="/cart" element={<ProtectedRoute><Cart /></ProtectedRoute>} />
      <Route path="/confirmed/:orderId" element={<ProtectedRoute><Confirmed /></ProtectedRoute>} />
      <Route path="/active" element={<ProtectedRoute><ActiveOrders /></ProtectedRoute>} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
