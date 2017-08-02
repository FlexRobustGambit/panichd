<?php

namespace Kordy\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'ticketit_departments';
    protected $fillable = ['department'];

    /**
     * Get directly associated users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany('Kordy\Ticketit\Models\Agent', 'ticketit_department')->orderBy('name');
    }
	
	/*
	 * Get main department of current one
	*/
	public function parent(){
		return $this->belongsTo('Kordy\Ticketit\Models\Department', 'department_id');
	}
	
	/*
	 * Get all sub-departments of current one
	*/
	public function children()
	{
		return $this->hasMany('Kordy\Ticketit\Models\Department', 'department_id', 'id');
	}
	
	/*
	 * Get all department / subdepartment related to current one
	 * For Parent: Get self + all children
	 * For child: Get self + parent
	 *
	 * @Return collection
	*/
	public function related()
	{
		$related = Collect([]);
		$related->push($this);
		$parent = $this->parent()->first();
		if ($parent){
			// Is Child
			$related->push($parent);
		}else{
			// Is Parent
			$related->push($this->children()->get());
		}
		return $related;
	}
	
	/*
	 * Get formatted concatenation of department and sub1
	 *
	 * @param bool $long full text or shortening for department
	 * 
	 * @Return string
	*/
	public function resume ($long = false)
	{
		if ($this->department_id){
			return ($long ? ucwords(mb_strtolower($this->department)) : $this->shortening).trans('ticketit::lang.colon').ucwords(mb_strtolower($this->sub1));
		}else{
			return ucwords(mb_strtolower($this->department));
		}
	}
	
	/*
	 * Get formatted department name
	 * 
	 * @Return string
	*/
	public function deptName(){
		return ucwords(mb_strtolower($this->department));
	}
	
	
	/*
	 * Get formatted department name as title
	 * 
	 * @Return string
	*/
	public function title ()
	{
		return trans('ticketit::lang.department-shortening').trans('ticketit::lang.colon').$this->deptName();
	}
}