<?php
/**
 * Created by PhpStorm.
 * User: lumin
 * Date: 16-12-12
 * Time: 下午4:34
 */

namespace NEUQOJ\Repository\Eloquent;


use NEUQOJ\Repository\Contracts\SoftDeletionInterface;
use NEUQOJ\Repository\Traits\getWhereCount;
use NEUQOJ\Repository\Traits\GetWhereCountTrait;
use NEUQOJ\Repository\Traits\InsertWithIdTrait;
use NEUQOJ\Repository\Traits\SoftDeletionTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class ProblemRepository extends AbstractRepository
{
    function model()
    {
        return "NEUQOJ\Repository\Models\Problem";
    }

    use InsertWithIdTrait;

    use GetWhereCountTrait;

    function getTotalCount()
    {
        return $this->model->all()->count();
    }

    function getTotalPublicCount()
    {
        return $this->model->where('is_public',1)->count();
    }

    function getProblems(int $page,int $size)
    {
        //只显示公开的题目
        return $this->model
            ->where('is_public',1)
            ->skip($size * --$page)
            ->take($size)
            ->select('problems.id','problems.title','problems.difficulty','problems.source','problems.submit','problems.accepted',
                'problems.is_public','problems.created_at','problems.updated_at','problem_tag_relations.tag_id',
                'problem_tags.name')
            ->leftJoin('problem_tag_relations','problems.id','=','problem_tag_relations.problem_id')
            ->leftJoin('problem_tags','problem_tag_relations.tag_id','=','problem_tags.id')
            ->orderBy('problems.id')
            ->get();
    }

//    function getProblemGroupTest(int $page,int $size)
//    {
//        return $this->model
//            ->groupBy('id')
//            ->where('is_public',1)
//            ->skip($size * --$page)
//            ->take($size)
//            ->leftJoin('problem_tag_relations','problems.id','=','problem_tag_relations.problem_id')
//            ->select('problems.id','problems.title','problems.difficulty','problems.source','problems.submit','problems.solved',
//                'problems.is_public','problems.created_at','problems.updated_at','problem_tag_relations.tag_id',
//                'problem_tag_relations.tag_title')
//            ->orderBy('id')
//            ->get();
//    }

    function getProblemsByAdmin(int $page,int $size)
    {
        //所有题目都显示
        return $this->model
            ->skip($size * --$page)
            ->take($size)
            ->select('problems.id','problems.title','problems.difficulty','problems.source','problems.submit','problems.accepted',
                'problems.is_public','problems.created_at','problems.updated_at','problem_tag_relations.tag_id',
                'problem_tags.name')
            ->leftJoin('problem_tag_relations','problems.id','=','problem_tag_relations.problem_id')
            ->leftJoin('problem_tags','problem_tag_relations.tag_id','=','problem_tags.id')
            ->orderBy('problems.id')
            ->get();
    }

    function getBy(string $param, string $value,array $columns=['*'])
    {
        return $this->model
            ->where($param, $value)
            ->select('problems.*','problem_tag_relations.tag_id','problem_tags.name')
            ->leftJoin('problem_tag_relations','problems.id','=','problem_tag_relations.problem_id')
            ->leftJoin('problem_tags','problem_tag_relations.tag_id','=','problem_tags.id')
            ->orderBy('problems.id')
            ->get();
    }

//    //覆盖方法
//
//    function doDeletion(int $id): bool
//    {
//        $item =  $this->model->where('id',$id)->onlyTrashed()->get()->first();
//
//        if($item == null)
//            return false;
//        if(!$item->forceDelete())
//            return false;
//
//        //删除文件系统中的相关内容
//        //文件操作写在这里并不合适，但是由于系统文件结构并不复杂所以就这么写了
//

//
//    }

    /*
     * 搜索
     */

    function getWhereLikeCount(string $pattern):int
    {
        //join过后的表的总数会出现不必要的重复，需要检测
        //只搜索公共题目

        $problems = $this->model
            ->where('is_public',1)
            ->leftJoin('problem_tag_relations','problems.id','=','problem_tag_relations.problem_id')
            ->leftJoin('problem_tags','problem_tag_relations.tag_id','=','problem_tags.id')
            ->select('problems.id')
            ->where('problems.title','like',$pattern)
            ->orWhere('problems.source','like',$pattern)
            ->orWhere('problem_tags.name','like',$pattern)
            ->orWhere('problems.id','like',$pattern)
            ->get();

        if($problems->first() == null)
            return 0;

        $count = $problems->count();


        $tempId = $problems->first()->id;

        foreach ($problems as $problem)
        {
            if($problem->id == $tempId)
                $count--;
            $tempId = $problem->id;
        }

        return $count+1;
    }

    //简易like搜索
    function getWhereLike(string $pattern,int $page = 1,int $size = 15,array $columns = ['*'])
    {
        if(!empty($size))
        {
            return $this->model
                ->where('is_public',1)
                ->leftJoin('problem_tag_relations','problems.id','=','problem_tag_relations.problem_id')
                ->leftJoin('problem_tags','problem_tag_relations.tag_id','=','problem_tags.id')
                ->where('problems.title','like',$pattern)
                ->orWhere('problems.source','like',$pattern)
                ->orWhere('problems.id','like',$pattern)
                ->orWhere('problem_tags.name','like',$pattern)
                ->select('problems.id','problems.title','problems.difficulty','problems.source','problems.submit','problems.accepted',
                    'problems.is_public','problems.created_at','problems.updated_at','problem_tag_relations.tag_id',
                    'problem_tags.name')
                ->orderBy('problems.id')
                ->skip($size * --$page)
                ->take($size)
                ->get($columns);
        }

        return null;
    }
}
