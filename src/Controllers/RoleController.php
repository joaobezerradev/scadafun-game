<?php

namespace App\Controllers;

use App\Environment;
use App\RPC\Opcodes;
use App\RPC\ReadPacket;
use App\RPC\WritePacket;
use App\RPC\Marshallizer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RoleController extends Controller {

    public $roleProtocol;

    public function __construct() {
        $version = Environment::getPwVersion();
        $this->roleProtocol = require_once(__DIR__ . "/../RPC/Version/{$version}.php");
    }

    public function characterRequest(Request $request): Response {
        $role = $this->characterGet($request->getParsedBody()['roleid']);
        return $this->response($role);
    }

    public function characternameRequest(Request $request): Response {
        $rolename = $request->getParsedBody()['rolename'];

        $getRoleId = new WritePacket();
        $getRoleId->WriteUInt32(-1);
        $getRoleId->WriteUString($rolename);
        $getRoleId->WriteUByte(0);
        $getRoleId->Pack(Opcodes::$role['getRoleId']);
        $getRoleId->Send(WritePacket::GAMEDBD_PORT);
        $response = new ReadPacket($getRoleId);
        $response2 = $response->ReadPacketInfo();
        $response->ReadUInt32();
        $response->ReadUInt32();
        $roleid = $response->ReadUInt32();
        $role = $this->characterGet($roleid);
        return $this->response($role);
    }

    public function meridianFull(Request $request): Response {
        $roleid = $request->getParsedBody()['roleid'];
        $role = $this->characterGet($roleid);
        $role["status"]["meridian_data"] = '000000500000000000000000000000050000006400003f920000000100000000000000000000000000000000000000000000000000000000';
        $this->characterPut($roleid, json_decode(json_encode($role), true));
        return $this->response("");
    }

    public function titleFull(Request $request): Response {
        $roleid = $request->getParsedBody()['roleid'];
        $role = $this->characterGet($roleid);
        $role["status"]["title_data"] = '8405cc0000000e050d058c050f05150516051705180519051a051b051c051d0583051e051f05200521052205230524052505840526052705280529052a052b052c052d052e052f0530053105320533053405350536053705380539053a053b053c053d053e053f054005410542054305850544058705450546054705480549054a0586054b054c054d054e054f058805500551055205530554055505560557055805590589055a055b055c055d055e055f0560056105620563056405650566056705680569056a056b056c056d056e056f05700571057205730574057505760577058b0578058a0579057a057b057c057d057e057f058005810582058d058e058f051106140615061606170618061d0629062b063e06610662066306640666066706680669066a066b066c066d066e066f067006710672067306740675067606770678067a067b067c067d067e067f0680068106820683068406850686068706880689068a068b068c068d068e068f06900691069206930694069b069c069f06a006a106a206a306a406a506a606a706a806a906aa06ab06ac06ad06ae0600000000';
        $this->characterPut($roleid, json_decode(json_encode($role), true));
        return $this->response("");
    }

    public function characterResponse(Request $request): Response {
        $roledecode = json_decode($request->getParsedBody()['data'], true);
        $this->characterPut($request->getParsedBody()['roleid'], $roledecode);
        return $this->response("");
    }

    public function resetBankRequest(Request $request): Response {
        $role = $this->characterGet($request->getParsedBody()['roleid']);
        $role['status']['storehousepasswd'] = '';
        $this->characterPut($request->getParsedBody()['roleid'], $role);
        return $this->response("");
    }

    public function renameRequest(Request $request): Response {
        $roleid = $request->getParsedBody()['roleid'];
        $oldname = $request->getParsedBody()['oldname'];
        $newname = $request->getParsedBody()['newname'];
        $this->rename($roleid, $oldname, $newname);
        return $this->response("");
    }

    public function rename($roleid, $oldname, $newname) {
        $renamerequest = new WritePacket();
        $renamerequest->WriteUInt32(-1); // Return Code
        $renamerequest->WriteUInt32($roleid); // roleid
        $renamerequest->WriteUString($oldname); // old name
        $renamerequest->WriteUString($newname); // new name
        $renamerequest->Pack(Opcodes::$role['renameRole']);
        $renamerequest->Send(WritePacket::GAMEDBD_PORT);
    }

    public function characterGet($roleid) {
        $getrolearg = new WritePacket();
        $getrolearg->WriteUInt32(-1);
        $getrolearg->WriteUInt32($roleid);
        $getrolearg->Pack(Opcodes::$role['getRole']);
        $getrolearg->Send(WritePacket::GAMEDBD_PORT);
        $getroleres = new ReadPacket($getrolearg);
        $info = $getroleres->ReadPacketInfo();
        $getroleres->ReadUInt32(); // ???
        $getroleres->ReadUInt32(); // Return Code
        $data = $getroleres->ReadBytes($info['Length']);
        $role = Marshallizer::unmarshal($data, $this->roleProtocol['role']);
        return $role;
    }

    public function factionRequest(Request $request): Response {
        $factionid = $request->getParsedBody()['factionid'];
        $getfaction = new WritePacket();
        $getfaction->WriteUInt32(-1);
        $getfaction->WriteUInt32($factionid);
        $getfaction->Pack(Opcodes::$role['getFaction']);
        $getfaction->Send(WritePacket::GAMEDBD_PORT);
        $getfactionres = new ReadPacket($getfaction);
        $info = $getfactionres->ReadPacketInfo();
        $getfactionres->ReadUInt32();
        $getfactionres->ReadUInt32();
        $data = [
            'fid' => $getfactionres->ReadUInt32(),
            'name' => $getfactionres->ReadString(),
            'level' => $getfactionres->ReadUByte(),
            'masterid' => $getfactionres->ReadUInt32(),
            'masterrole' => $getfactionres->ReadUByte(),
            'count' => $getfactionres->ReadCUInt32(),
        ];
        for ($i = 0; $i < $data['count']; $i++) {
            $data['members'][] = [
                'memberid' => $getfactionres->ReadUInt32(),
                'memberrole' => $getfactionres->ReadUByte(),
            ];
        }
        $data['announce'] = $getfactionres->ReadString();
        $data['sysinfo'] = $getfactionres->ReadString();
        return $this->response($data);
    }

    public function userfactionRequest(Request $request): Response {
        $roleid = $request->getParsedBody()['roleid'];
        $getfaction = new WritePacket();
        $getfaction->WriteUInt32(-1);
        $getfaction->WriteUInt32(1);
        $getfaction->WriteUInt32($roleid);
        $getfaction->Pack(Opcodes::$role['getUserFaction']);
        $getfaction->Send(WritePacket::GAMEDBD_PORT);
        $getfactionres = new ReadPacket($getfaction);
        $info = $getfactionres->ReadPacketInfo();

        $data = [
            'unk1' => $getfactionres->ReadUInt32(),
            'unk2' => $getfactionres->ReadUInt32(),
            'roleid' => $getfactionres->ReadUInt32(),
            'name' => $getfactionres->ReadString(),
            'factionid' => $getfactionres->ReadUInt32(),
            'cls' => $getfactionres->ReadUByte(),
            'role' => $getfactionres->ReadUByte(),
            'delayexpel' => $getfactionres->ReadOctets(),
            'extend' => $getfactionres->ReadOctets(),
            'nickname' => $getfactionres->ReadString(),
        ];
        return $this->response($data);
    }

    public function characterPut($roleid, $data) {
        $putrolearg = new WritePacket();
        $putrolearg->WriteUInt32(-1); // RETURN CODE
        $putrolearg->WriteUInt32($roleid); // ROLEID
        $putrolearg->WriteUByte(1); //OVERWRITE
        $putrolearg->WriteBytes(Marshallizer::marshal($data, $this->roleProtocol['role'])); // ROLE
        $putrolearg->Pack(Opcodes::$role['putRole']);
        $putrolearg->Send(WritePacket::GAMEDBD_PORT);
    }

    public function banRole(Request $request): void {
        $Packet = new WritePacket();
        $Packet->WriteUInt32(-1); // gmroleid
        $Packet->WriteUInt32(0); // ssid
        $Packet->WriteUInt32($request->getParsedBody()['roleid']); // ID role/account
        $Packet->WriteUInt32($request->getParsedBody()['time']); // Time
        $Packet->WriteUString($request->getParsedBody()['reason']); //Reason
        $Packet->Pack(0x168); //Ban role
        $Packet->Send(WritePacket::GDELIVERYD_PORT, true);
    }

    public function muteRole(Request $request): void {
        $Packet = new WritePacket();
        $Packet->WriteUInt32(-1); // gmroleid
        $Packet->WriteUInt32(0); // ssid
        $Packet->WriteUInt32($request->getParsedBody()['roleid']); // ID role/account
        $Packet->WriteUInt32($request->getParsedBody()['time']); // Time
        $Packet->WriteUString($request->getParsedBody()['reason']); //Reason
        $Packet->Pack(0x164); //Ban role
        $Packet->Send(WritePacket::GDELIVERYD_PORT, true);
    }

    public function banAccount(Request $request): Response {
        $Packet = new WritePacket();
        $Packet->WriteUInt32(-1); // always
        $Packet->WriteUByte($request->getParsedBody()['operation']); // operation
        $Packet->WriteUInt32(-1); // gmuserid
        $Packet->WriteUInt32(-1); // source
        $Packet->WriteUInt32($request->getParsedBody()['userid']); // ID role/account
        $Packet->WriteUInt32($request->getParsedBody()['time']); // Time
        $Packet->WriteUString($request->getParsedBody()['reason']); //Reason
        $Packet->Pack(0x1F44); //Ban account
        $Packet->Send(WritePacket::GAMEDBD_PORT);
        return $this->response("ok");
    }

}
