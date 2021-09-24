<?php
/**
 * Created by PhpStorm.
 * User: NEUQer
 * Date: 17/1/2
 * Time: 下午8:45
 */

namespace NEUQOJ\Http\Controllers;


use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use NEUQOJ\Exceptions\FormValidatorException;
use NEUQOJ\Exceptions\NoPermissionException;
use NEUQOJ\Services\MessageService;
use NEUQOJ\Services\PrivilegeService;
use NEUQOJ\Services\RoleService;

class MessageController extends Controller
{
    public function sendMessage(Request $request, MessageService $messageService)
    {

        //表单认证
        $validator = Validator::make($request->all(), [
            'from_id' => 'required',
            'to_id' => 'required',
            'from_name' => 'required|max:100',
            'to_name' => 'required|max:100',
            'contents' => 'required',
            'title' => 'required|max:100'
        ]);

        if ($validator->fails()) {
            $data = $validator->getMessageBag()->all();
            throw new FormValidatorException($data);
        }
        $data = [
            'from_name' => $request->from_name,
            'to_name' => $request->to_name,
            'content' => $request->contents,
            'title' => $request->title,
            'is_read' => 0
        ];

        if ($messageService->sendMessage($request->from_id, $request->to_id, $data))
            return response()->json([
                'code' => 0
            ]);
    }

    public function getUserMessages(Request $request, MessageService $messageService)
    {
        //表单认证
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'size' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            $data = $validator->getMessageBag()->all();
            throw new FormValidatorException($data);
        }

        $userId = $request->user->id;
        $page = $request->input('page', 1);
        $size = $request->input('size', 20);

        $message = null;
        //只取了发送人信息和标题
        $message = $messageService->getUserMessages($userId, $page, $size,
            ['id', 'is_read', 'from_id', 'from_name', 'title', 'created_at']);

        return response()->json([
            'code' => 0,
            'data' => $message
        ]);
    }

    public function getUserUnreadMessages(Request $request, MessageService $messageService)
    {

        //表单认证
        $validator = Validator::make($request->all(), [
            'userId' => 'required',
            'page' => 'integer|min:1',
            'size' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            $data = $validator->getMessageBag()->all();
            throw new FormValidatorException($data);
        }

        $userId = $request->input('userId');
        $page = $request->input('page', 1);
        $size = $request->input('size', 20);
        $message = null;
        $message = $messageService->getUnreadMessages($userId, $page, $size,
            ['id', 'from_id', 'from_name', 'title', 'created_at']);

        return response()->json([
            'code' => 0,
            'data' => $message
        ]);
    }

    public function getUserMessageCount(MessageService $messageService, Request $request)
    {


        $messageCount = 0;

        $messageCount = $messageService->getUserMessageCount($request->user->id);

        return response()->json([
            'code' => 0,
            'data' => [
                'count' => $messageCount
            ]
        ]);

    }

    public function getUserUnreadMessageCount(MessageService $messageService, int $userId)
    {

        $messageCount = 0;

        $messageCount = $messageService->getUnreadMessagesCount($userId);

        return response()->json([
            'code' => 0,
            'data' => [
                'count' => $messageCount
            ]
        ]);
    }

    public function deleteOwnMessage(Request $request, MessageService $messageService)
    {

        //表单认证
        $validator = Validator::make($request->all(), [
            'userId' => 'integer|required',
            'mId' => 'integer|required'
        ]);

        if ($validator->fails()) {
            $data = $validator->getMessageBag()->all();
            throw new FormValidatorException($data);
        }

        $userId = $request->input('userId');
        $mId = $request->input('mId');


        if (!($messageService->getUserMessagesByMult(['to_id' => $userId, 'id' => $mId], ['id'])))
            throw new NoPermissionException();

        if ($messageService->deleteMessage($userId, $mId))
            return response()->json([
                'code' => 0
            ]);
    }

    public function deleteMessage(Request $request, MessageService $messageService, RoleService $roleService)
    {
        //表单认证
        $validator = Validator::make($request->all(), [
            'userId' => 'integer|required',
            'mId' => 'integer|required',
            'operatorId' => 'integer|required',
        ]);

        if ($validator->fails()) {
            $data = $validator->getMessageBag()->all();
            throw new FormValidatorException($data);
        }

        $messageId = $request->input('mId');
        $userId = $request->input('userId');
        $operatorId = $request->input('operatorId');
        //管理员才可以删除别人的消息
        if (!($roleService->hasRole($operatorId, 'admin')))
            throw new NoPermissionException();

        if ($messageService->deleteMessage($userId, $messageId))
            return response()->json([
                'code' => 0
            ]);
    }

    //点开某条消息
    public function checkUserMessage($messageId, MessageService $messageService)
    {
        $message = $messageService->getMessage($messageId, ['from_name', 'from_id', 'content', 'title']);

        if ($messageService->checkUserMessage($messageId))//更改状态
            return response()->json([
                'code' => 0,
                'data' => [
                    'message' => $message
                ]
            ]);
    }
}