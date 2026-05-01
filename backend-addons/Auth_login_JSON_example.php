<?php
/**
 * Optional: JSON response for POST auth/login when Accept: application/json
 *
 * In your Auth controller (e.g. Auth::login), after validating credentials:
 *
 * if ($this->request->getHeaderLine('Accept') === 'application/json' ||
 *     str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
 *
 *     if (/* validation failed: empty *\/) {
 *         return $this->response->setJSON(['success' => false, 'error' => 'Username and password are required.'])->setStatusCode(200);
 *     }
 *     if (/* invalid user or wrong password *\/) {
 *         return $this->response->setJSON(['success' => false, 'error' => 'Invalid credentials.'])->setStatusCode(200);
 *     }
 *     if (/* account locked (e.g. failed_attempts >= 5) *\/) {
 *         return $this->response->setJSON(['success' => false, 'error' => 'Account locked. Please contact administrator.'])->setStatusCode(200);
 *     }
 *
 *     // Success: set session as you already do, then:
 *     $user = session()->get('user'); // or however you store user
 *     return $this->response->setJSON([
 *         'success' => true,
 *         'user' => [
 *             'id'   => $user['id'],
 *             'name' => $user['name'],
 *             'role' => $user['role'] ?? '',
 *         ]
 *     ])->setStatusCode(200);
 * }
 *
 * // Otherwise continue with your normal redirect (302 to dashboard or back to login).
 */
