<?php

namespace App\Services\JHCIS;

use App\Services\CacheService;

/**
 * Circuit Breaker for JHCIS Connections
 * 
 * Prevents cascade failures by opening circuit after threshold failures
 */
class JHCISCircuitBreaker
{
    private CacheService $cache;
    private int $failureThreshold = 5;
    private int $timeout = 60; // seconds
    private int $halfOpenAttempts = 3;
    
    public function __construct(int $failureThreshold = 5, int $timeout = 60)
    {
        $this->cache = new CacheService();
        $this->failureThreshold = $failureThreshold;
        $this->timeout = $timeout;
    }
    
    /**
     * Check if circuit is open
     * 
     * @param int $hospitalId
     * @return bool
     */
    public function isOpen(int $hospitalId): bool
    {
        $state = $this->getState($hospitalId);
        
        if ($state['status'] === 'open') {
            // Check if timeout expired
            if (time() - $state['opened_at'] >= $this->timeout) {
                // Move to half-open state
                $this->setState($hospitalId, 'half-open');
                return false;
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Record successful operation
     * 
     * @param int $hospitalId
     * @return void
     */
    public function recordSuccess(int $hospitalId): void
    {
        $state = $this->getState($hospitalId);
        
        if ($state['status'] === 'half-open') {
            $state['half_open_successes']++;
            
            if ($state['half_open_successes'] >= $this->halfOpenAttempts) {
                // Close circuit
                $this->setState($hospitalId, 'closed');
            } else {
                $this->saveState($hospitalId, $state);
            }
        } else {
            // Reset failure count
            $this->setState($hospitalId, 'closed');
        }
    }
    
    /**
     * Record failed operation
     * 
     * @param int $hospitalId
     * @return void
     */
    public function recordFailure(int $hospitalId): void
    {
        $state = $this->getState($hospitalId);
        
        if ($state['status'] === 'half-open') {
            // Failure in half-open, reopen circuit
            $this->setState($hospitalId, 'open');
            return;
        }
        
        $state['failures']++;
        
        if ($state['failures'] >= $this->failureThreshold) {
            // Open circuit
            $this->setState($hospitalId, 'open');
        } else {
            $this->saveState($hospitalId, $state);
        }
    }
    
    /**
     * Get circuit state
     * 
     * @param int $hospitalId
     * @return array
     */
    private function getState(int $hospitalId): array
    {
        $key = "circuit_breaker:jhcis:{$hospitalId}";
        $state = $this->cache->get($key);
        
        if (!$state) {
            return [
                'status' => 'closed',
                'failures' => 0,
                'opened_at' => null,
                'half_open_successes' => 0
            ];
        }
        
        return $state;
    }
    
    /**
     * Set circuit state
     * 
     * @param int $hospitalId
     * @param string $status
     * @return void
     */
    private function setState(int $hospitalId, string $status): void
    {
        $state = [
            'status' => $status,
            'failures' => 0,
            'opened_at' => $status === 'open' ? time() : null,
            'half_open_successes' => 0
        ];
        
        $this->saveState($hospitalId, $state);
    }
    
    /**
     * Save circuit state
     * 
     * @param int $hospitalId
     * @param array $state
     * @return void
     */
    private function saveState(int $hospitalId, array $state): void
    {
        $key = "circuit_breaker:jhcis:{$hospitalId}";
        $this->cache->set($key, $state, $this->timeout * 2);
    }
    
    /**
     * Reset circuit
     * 
     * @param int $hospitalId
     * @return void
     */
    public function reset(int $hospitalId): void
    {
        $this->setState($hospitalId, 'closed');
    }
    
    /**
     * Get circuit status
     * 
     * @param int $hospitalId
     * @return array
     */
    public function getStatus(int $hospitalId): array
    {
        return $this->getState($hospitalId);
    }
}
