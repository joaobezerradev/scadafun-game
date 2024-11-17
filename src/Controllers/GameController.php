<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\RPC\Opcodes;
use App\RPC\ReadPacket;
use App\RPC\WritePacket;
use App\RPC\Structs\GRoleInventory;

class GameController extends Controller 
{
    public function broadcastRequest(Request $request, Response $response, array $args): Response 
    {
        $data = $request->getParsedBody();
        
        $broadcastData = [
            'sender' => ($data['roleid'] ?? 0),
            'message' => $data['message'],
            'channel' => intval($data['channel'])
        ];
        
        self::broadcast($broadcastData);
        return $this->response();
    }

    public static function broadcast($data) 
    {
        $broadcastPacket = new WritePacket();
        $broadcastPacket->WriteUByte($data['channel']);
        $broadcastPacket->WriteUByte(0);
        $broadcastPacket->WriteUInt32($data['sender']);
        $broadcastPacket->WriteUString($data['message']);
        $broadcastPacket->WriteOctets("");
        $broadcastPacket->Pack(Opcodes::$game['broadcast']);
        $broadcastPacket->Send(WritePacket::GPROVIDER_PORT);
        return $broadcastPacket;
    }

    public function onlineListRequest(Request $request, Response $response, array $args): Response 
    {
        $users = [];
        $handler = -1;
        $count = 0;

        do {
            try {
                $onlinesPacket = new WritePacket();
                $onlinesPacket->WriteInt32(32); // GM ROLEID
                $onlinesPacket->WriteInt32(1); // Localsid
                $onlinesPacket->WriteUInt32($handler); // Handler
                $onlinesPacket->WriteOctets(1); // Cond
                $onlinesPacket->Pack(Opcodes::$game['onlines']);
                $onlinesPacket->Send(WritePacket::GDELIVERYD_PORT);
                $onlinesResponse = new ReadPacket($onlinesPacket);
                $info = $onlinesResponse->ReadPacketInfo();
                $data = [
                    'packet' => $info,
                    'retcode' => $onlinesResponse->ReadUInt32(),
                    'gmroleid' => $onlinesResponse->ReadInt32(),
                    'localsid' => $onlinesResponse->ReadInt32(),
                    'handler' => $onlinesResponse->ReadUInt32(),
                    'counter' => $onlinesResponse->ReadCUInt32(),
                ];
                for ($i = 0; $i < $data['counter']; $i++) {
                    $user = [
                        'userid' => $onlinesResponse->swap_endian($onlinesResponse->ReadUInt32()),
                        'roleid' => $onlinesResponse->ReadUInt32(),
                        'linkid' => $onlinesResponse->ReadUInt32(),
                        'localsid' => $onlinesResponse->ReadUInt32(),
                        'gsid' => $onlinesResponse->ReadUInt32(),
                        'status' => $onlinesResponse->ReadByte(),
                        'name' => $onlinesResponse->ReadString()
                    ];
                    $users[] = $user;
                }
                $handler = $data['handler'];
                $count += $data['counter'];
            } catch (\ErrorException $e) {
                
            }
        } while ($handler !== 4294967295);

        return $this->response([
            'users' => $users,
            'counter' => $count
        ]);
    }

    public function emailRequest(Request $request, Response $response, array $args): Response 
    {
        $data = $request->getParsedBody();
        
        $item = new GRoleInventory();
        $item->pos = 0;
        $item->id = ($data['itemid'] ?? 0);
        $item->count = ($data['stack'] ?? 0);
        $item->max_count = ($data['max_stack'] ?? 0);
        $item->data = ($data['data'] ?? 0);
        $item->proctype = ($data['proctype'] ?? 0);
        $item->expire_date = ($data['expire_date'] ?? 0);
        $item->guid1 = ($data['guid1'] ?? 0);
        $item->guid2 = ($data['guid2'] ?? 0);
        $item->mask = ($data['mask'] ?? 0);

        $emailData = [
            'receiver' => $data['roleid'],
            'title' => $data['title'] ?? "",
            'context' => $data['context'] ?? "",
            'attach_item' => $item,
            'attach_money' => ($data['money'] ?? 0),
        ];

        $this->email($emailData);
        return $this->response();
    }

    public function setDoubleRate(Request $request, Response $response, array $args): Response 
    {
        $data = $request->getParsedBody();
        
        $ratePacket = new WritePacket();
        $ratePacket->WriteUInt32($data['rate'] ?? 1); // Taxa de exp/sp (1 = normal, 2 = double)
        $ratePacket->Pack(Opcodes::$game['GMAttr']);
        $ratePacket->Send(WritePacket::GDELIVERYD_PORT);
        
        return $this->response();
    }

    private function email($data) 
    {
        $email = new WritePacket();
        $email->WriteUInt32(1);
        $email->WriteUInt32(32);
        $email->WriteUByte(3);
        $email->WriteUInt32($data['receiver']);
        $email->WriteUString($data['title']);
        $email->WriteUString($data['context']);
        // GROLEINVENTORY
        $email->WriteUInt32($data['attach_item']->id);
        $email->WriteUInt32($data['attach_item']->pos);
        $email->WriteUInt32($data['attach_item']->count);
        $email->WriteUInt32($data['attach_item']->max_count);
        $email->WriteOctets($data['attach_item']->data);
        $email->WriteUInt32($data['attach_item']->proctype);
        $email->WriteUInt32($data['attach_item']->expire_date);
        $email->WriteUInt32($data['attach_item']->guid1);
        $email->WriteUInt32($data['attach_item']->guid2);
        $email->WriteUInt32($data['attach_item']->mask);
        // END GROLEINVENTORY
        $email->WriteUInt32($data['attach_money']);
        $email->Pack(Opcodes::$game['email']);
        $email->Send(WritePacket::GDELIVERYD_PORT);
    }
}
