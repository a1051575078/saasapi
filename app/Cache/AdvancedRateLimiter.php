<?php

namespace App\Cache;


use Illuminate\Cache\RateLimiter;

class AdvancedRateLimiter extends RateLimiter{
    public function hit($key, $decaySeconds = 60){
        if (is_array($decaySeconds)) {
            if (! $this->cache->has($key.':timer')) {
                if (! $this->cache->has($key.':step')) {
                    $this->cache->add($key.':step', 0, 86400);
                }else{
                    $this->cache->increment($key.':step');
                }
            }
            $step = $this->cache->get($key.':step', 0);
            $step = $step < count($decaySeconds) ? $step : count($decaySeconds) - 1;
            $decaySeconds = $decaySeconds[$step];
        }
        return parent::hit($key, $decaySeconds);
    }
    public function clear($key){
        $this->cache->forget($key.':step');
        parent::clear($key);
    }
}