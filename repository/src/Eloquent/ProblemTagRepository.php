<?php
/**
 * Created by PhpStorm.
 * User: yz
 * Date: 16-12-15
 * Time: 下午7:23
 */

namespace NEUQOJ\Repository\Eloquent;




use NEUQOJ\Repository\Traits\InsertWithId;
use NEUQOJ\Repository\Traits\InsertWithIdTrait;

class ProblemTagRepository extends AbstractRepository
{
    function model()
    {
        return "NEUQOJ\Repository\Models\ProblemTag";
    }
    use InsertWithIdTrait;
}