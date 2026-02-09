<?php 
namespace Tables;
use Classes\BaseTable;

class Students extends BaseTable {
    
    protected $table = "students";

    protected $fillable = [];

    protected $guarded = [];

    protected $hidden = [];

    protected $timestamps = false;
}
?>