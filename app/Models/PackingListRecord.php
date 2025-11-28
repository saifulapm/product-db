<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackingListRecord extends Model
{
    /**
     * This model doesn't use a database table
     */
    public $timestamps = false;
    
    /**
     * Disable table name requirement
     */
    protected $table = 'packing_list_records';
    
    /**
     * Indicates if the model should be timestamped.
     */
    public $incrementing = false;
    
    /**
     * Allow mass assignment for all attributes
     */
    protected $guarded = [];
    
    /**
     * Create a new instance from array data
     */
    public static function fromArray(array $data): self
    {
        $instance = new static();
        foreach ($data as $key => $value) {
            $instance->setAttribute($key, $value);
        }
        return $instance;
    }
    
    /**
     * Get the value of the model's primary key
     */
    public function getKey()
    {
        return $this->getAttribute('id');
    }
    
    /**
     * Get the primary key for the model
     */
    public function getKeyName()
    {
        return 'id';
    }
    
    /**
     * Override to prevent database queries - return empty query builder
     */
    public function newQuery()
    {
        $connection = app('db')->connection();
        $grammar = $connection->getQueryGrammar();
        $processor = $connection->getPostProcessor();
        
        $query = new \Illuminate\Database\Query\Builder($connection, $grammar, $processor);
        $query->from($this->getTable());
        
        return new \Illuminate\Database\Eloquent\Builder($query);
    }
    
    /**
     * Override to prevent database saves
     */
    public function save(array $options = [])
    {
        // This is a read-only model
        return false;
    }
    
    /**
     * Override to prevent database deletes
     */
    public function delete()
    {
        // This is a read-only model
        return false;
    }
    
    /**
     * Override exists check since we're not using a real table
     */
    public function exists()
    {
        return true;
    }
}

