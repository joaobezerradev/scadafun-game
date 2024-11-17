<?php

namespace App\Controllers;

use App\RPC\Opcodes;
use App\RPC\ReadPacket;
use App\RPC\WritePacket;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController extends Controller {


    public function __construct() {
        
    }

    public function rolesRequest(Request $request, Response $response, array $args): Response {
        $getUserRoles = new WritePacket();
        $getUserRoles->WriteUInt32(-1);
        $getUserRoles->WriteUInt32($args['userid']);
        $getUserRoles->Pack(Opcodes::$user['userRoles']);
        $getUserRoles->Send(WritePacket::GAMEDBD_PORT);
        $result = new ReadPacket($getUserRoles);
        $result->ReadPacketInfo();
        $result->ReadInt32(); // ???
        $result->ReadInt32(); // Return Code
        $data['count'] = $result->ReadCUInt32(); // RoleCount
        for ($i = 0; $i < $data['count']; $i++) {
            $data['users'][] = [
                'roleid' => $result->ReadUInt32(),
                'rolename' => $result->ReadString()
            ];
        }
        return $this->response($data);
    }

    public function removelockRequest(Request $request, Response $response, array $args): Response 
    {
        $removeLock = new WritePacket();
        $removeLock->WriteUInt32(-1);
        $removeLock->WriteUInt32($args['userid']);
        $removeLock->Pack(Opcodes::$user['removeLock']);
        $removeLock->Send(WritePacket::GAMEDBD_PORT);
        
        return $this->response();
    }

}
