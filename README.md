# lock
Exclusive &amp; Read/Write locking based on MySQL Locking Service 

## Requirements

- php >= 7.1
- PDO extension

Need to install the locking service UDFs as described in MySQL docs: 

https://dev.mysql.com/doc/refman/5.7/en/locking-service-udf-interface.html



## Install via Composer

`composer require team-a/lock:^1.0.0`

## Examples

- Inject PDO object.

```php
    AbstractDb::setPdo(
        new \PDO($dsn, $user, $password)
    );
```
    
- Define and use your own lock class based on TeamA\Lock\AbstractDBExclusive or TeamA\Lock\AbstractDBExtended.

- Exclusive lock example:

```php
    class Point extends AbstractDbExclusive
    {
        protected function __construct(
            int      $providerId, 
            ? string $providerPointId, 
            ? string $providerPointEssentialId
        )
        {
            parent::__construct([
                $providerId, $providerPointId, $providerPointEssentialId
            ]);
        }
    }
    
    /* ... */
    
    $pointLock = new Point($pId, $pPointId, null);
    
    try {
        $pointLock->lock();
        
        $this->_db->beginTransaction();
        
        // do smth.
        
        $this->_db->commit();
        
        return true;  
                           
      } catch (TeamA\Lock\TimeoutException $e) {
      
         return false;
         
      } catch (\Exception $e) {
      
         $this->_db->rollback();
         throw $e;
         
      } finally {
      
         $pointLock->releaseIfLocked();
         
      }
``` 
          
- Read/Write locking example:   
    
```php  
    class GeoBinding extends AbstractDbExtended
    {
        public function __construct(int $departureProviderId)
        {
            parent::__construct(func_get_args());
        }
    }  
    
    /* ... */
    
    $lock = new GeoBinding(static::_getProviderId());
    
    try {
        $lock->lockWrite();
        
        $this->_db->beginTransaction();
        
        // do smth.
        
        $this->_db->commit();
        
        return true;  
                         
    } catch (TeamA\Lock\TimeoutException $e) {
    
       return false;
       
    } catch (\Exception $e) {
    
       $this->_db->rollback();
       throw $e;
       
    } finally {
    
       $lock->release();
       
    }   
    
    /* ... */
    
    $lock = new GeoBinding(static::_getProviderId());
        
    try {
        $lock->lockRead();
        
        $this->_db->beginTransaction();
        
        // do smth. else...  
        
```     
       
