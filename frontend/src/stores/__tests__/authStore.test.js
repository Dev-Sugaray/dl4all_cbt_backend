import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../authStore'
import axios from 'axios'

// Mock axios
vi.mock('axios', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
    defaults: {
      headers: {
        common: {}
      }
    }
  }
}))

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
global.localStorage = localStorageMock

describe('Auth Store', () => {
  let authStore

  beforeEach(() => {
    setActivePinia(createPinia())
    authStore = useAuthStore()
    vi.clearAllMocks()
    localStorageMock.getItem.mockReturnValue(null)
  })

  afterEach(() => {
    vi.clearAllTimers()
  })

  describe('Initial State', () => {
    it('should have correct initial state', () => {
      expect(authStore.user).toBeNull()
      expect(authStore.token).toBeNull()
      expect(authStore.refreshToken).toBeNull()
      expect(authStore.isAuthenticated).toBe(false)
      expect(authStore.tokenExpiry).toBeNull()
      expect(authStore.isLoading).toBe(false)
      expect(authStore.error).toBeNull()
    })
  })

  describe('Login', () => {
    it('should login successfully with valid credentials', async () => {
      const mockResponse = {
        data: {
          token: 'mock-token',
          refresh_token: 'mock-refresh-token',
          user: { id: 1, name: 'Test User', role: 'student' },
          expires_in: 3600
        }
      }
      
      axios.post.mockResolvedValueOnce(mockResponse)
      
      const result = await authStore.login({ email: 'test@test.com', password: 'password' })
      
      expect(result.success).toBe(true)
      expect(authStore.token).toBe('mock-token')
      expect(authStore.refreshToken).toBe('mock-refresh-token')
      expect(authStore.user).toEqual(mockResponse.data.user)
      expect(authStore.isAuthenticated).toBe(true)
      expect(localStorageMock.setItem).toHaveBeenCalledWith('token', 'mock-token')
      expect(localStorageMock.setItem).toHaveBeenCalledWith('refreshToken', 'mock-refresh-token')
      expect(axios.defaults.headers.common['Authorization']).toBe('Bearer mock-token')
    })

    it('should handle login failure', async () => {
      const mockError = {
        response: {
          data: {
            error: 'Invalid credentials'
          }
        }
      }
      
      axios.post.mockRejectedValueOnce(mockError)
      
      await expect(authStore.login({ email: 'test@test.com', password: 'wrong' }))
        .rejects.toThrow('Invalid credentials')
      
      expect(authStore.isAuthenticated).toBe(false)
      expect(authStore.error).toBe('Invalid credentials')
    })
  })

  describe('Token Refresh', () => {
    it('should refresh token successfully', async () => {
      authStore.refreshToken = 'mock-refresh-token'
      
      const mockResponse = {
        data: {
          token: 'new-token',
          expires_in: 3600
        }
      }
      
      axios.post.mockResolvedValueOnce(mockResponse)
      
      const newToken = await authStore.refreshAccessToken()
      
      expect(newToken).toBe('new-token')
      expect(authStore.token).toBe('new-token')
      expect(localStorageMock.setItem).toHaveBeenCalledWith('token', 'new-token')
    })

    it('should logout when refresh token is not available', async () => {
      authStore.refreshToken = null
      
      await expect(authStore.refreshAccessToken())
        .rejects.toThrow('No refresh token available')
      
      expect(authStore.isAuthenticated).toBe(false)
    })
  })

  describe('Token Expiry', () => {
    it('should detect expired token', () => {
      authStore.tokenExpiry = new Date(Date.now() - 1000).toISOString() // 1 second ago
      
      expect(authStore.isTokenExpired()).toBe(true)
    })

    it('should detect valid token', () => {
      authStore.tokenExpiry = new Date(Date.now() + 3600000).toISOString() // 1 hour from now
      
      expect(authStore.isTokenExpired()).toBe(false)
    })
  })

  describe('Logout', () => {
    it('should clear all authentication data', () => {
      authStore.token = 'token'
      authStore.refreshToken = 'refresh-token'
      authStore.user = { id: 1, name: 'Test User' }
      authStore.isAuthenticated = true
      
      authStore.logout()
      
      expect(authStore.token).toBeNull()
      expect(authStore.refreshToken).toBeNull()
      expect(authStore.user).toBeNull()
      expect(authStore.isAuthenticated).toBe(false)
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('token')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('refreshToken')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('user')
    })
  })

  describe('Student Methods', () => {
    it('should identify student user correctly', () => {
      authStore.user = { id: 1, name: 'Test Student', role: 'student' }
      
      expect(authStore.isStudent()).toBe(true)
      expect(authStore.getStudentId()).toBe(1)
    })

    it('should return false for non-student user', () => {
      authStore.user = { id: 1, name: 'Test Admin', role: 'admin' }
      
      expect(authStore.isStudent()).toBe(false)
      expect(authStore.getStudentId()).toBeNull()
    })

    it('should return null when no user is logged in', () => {
      authStore.user = null
      
      expect(authStore.isStudent()).toBe(false)
      expect(authStore.getStudentId()).toBeNull()
    })
  })

  describe('Fetch User', () => {
    it('should fetch user profile successfully', async () => {
      authStore.token = 'valid-token'
      authStore.tokenExpiry = new Date(Date.now() + 3600000).toISOString()
      
      const mockUser = { id: 1, name: 'Test User', role: 'student' }
      axios.get.mockResolvedValueOnce({ data: mockUser })
      
      const user = await authStore.fetchUser()
      
      expect(user).toEqual(mockUser)
      expect(authStore.user).toEqual(mockUser)
      expect(authStore.isAuthenticated).toBe(true)
    })

    it('should logout on 401 error', async () => {
      authStore.token = 'invalid-token'
      authStore.tokenExpiry = new Date(Date.now() + 3600000).toISOString()
      
      const mockError = {
        response: {
          status: 401,
          data: { error: 'Unauthorized' }
        }
      }
      
      axios.get.mockRejectedValueOnce(mockError)
      
      const user = await authStore.fetchUser()
      
      expect(user).toBeNull()
      expect(authStore.isAuthenticated).toBe(false)
    })
  })
})