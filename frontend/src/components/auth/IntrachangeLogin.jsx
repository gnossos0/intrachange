import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import './Auth.css';

const IntrachangeLogin = () => {
  const [credentials, setCredentials] = useState({
    email: '',
    password: ''
  });
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  
  const navigate = useNavigate();

  const updateField = (event) => {
    const { name, value } = event.target;
    setCredentials(prev => ({
      ...prev,
      [name]: value
    }));
    if (errorMessage) setErrorMessage('');
  };

  const processLogin = async (event) => {
    event.preventDefault();
    setIsLoading(true);
    setErrorMessage('');

    try {
      const result = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(credentials),
      });

      const response = await result.json();

      if (response.success) {
        localStorage.setItem('authToken', response.token);
        localStorage.setItem('user', JSON.stringify(response.user));
        navigate('/dashboard');
      } else {
        setErrorMessage(response.message || 'Authentication failed');
      }
    } catch (error) {
      setErrorMessage('Connection error. Please check your internet and try again.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="page">
        <header>
          <h1>Intrachange</h1>
        </header>

        <main>
          <div className="auth-form-container">
            <h2>Sign In to Your Account</h2>
            
            <form onSubmit={processLogin} className="auth-form">
              {errorMessage && (
                <div className="error-message">
                  {errorMessage}
                </div>
              )}

              <div className="form-group">
                <label htmlFor="email">Email Address</label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  required
                  value={credentials.email}
                  onChange={updateField}
                  placeholder="your@email.com"
                  disabled={isLoading}
                />
              </div>

              <div className="form-group">
                <label htmlFor="password">Password</label>
                <input
                  id="password"
                  name="password"
                  type="password"
                  required
                  value={credentials.password}
                  onChange={updateField}
                  placeholder="Enter your password"
                  disabled={isLoading}
                />
              </div>

              <button
                type="submit"
                disabled={isLoading}
                className="auth-button primary"
              >
                {isLoading ? (
                  <>
                    <span className="spinner"></span>
                    Signing In...
                  </>
                ) : (
                  'Sign In'
                )}
              </button>

              <div className="auth-links">
                <Link to="/forgot-password" className="forgot-link">
                  Forgot your password?
                </Link>
              </div>
            </form>
          </div>
        </main>

        <footer className="nav-footer">
          <span>Don't have an account?</span>
          <Link to="/register">Create one here</Link>
        </footer>
      </div>
    </div>
  );
};

export default IntrachangeLogin;