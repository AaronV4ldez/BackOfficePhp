<?php

namespace Data;

class UserModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'users');
    }

    public function getUserById(int $id)
    {
        return $this->load(array('id = ?', $id));
        //return $this->cast();
    }

    public function getUserByEmail(string $email)
    {
        return $this->load(array('userlogin=?', $email));
    }

    public function getUserByActivationToken(string $token)
    {
        return $this->load(array('activation_token=?', $token));
    }

    public function createUser(
        string $fullname, 
        string $email, 
        string $phone, 
        string $password, 
        int $usertype = 1, 
        $country_code = '52'): array
    {
        // $usertype: 1=>mobile, 2=>operator, 3=>admin, 4=>superadmin, 5=>socialbullshit 
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $nu = new UserModel();
        $nu->fullname = $fullname;
        $nu->password_hash = $password_hash;
        $nu->phone = preg_replace('/[^0-9]/', '', $phone);
        $nu->userlogin = $email;
        $nu->usertype = $usertype;
        $nu->country_code = preg_replace('/[^0-9]/', '', $country_code);
        if ($usertype > 1) {
            $nu->user_pass_set = 1;
            $nu->sms_validated = 1;
            $nu->account_verified = 1;
        }
        $nu->save();

        $nu->activation_token = md5(strval($nu->id) . $nu->userlogin . $nu->fullname);
        $nu->save();

        return $nu->cast();
    }

    public function createUserActivationRecord(int $user_id)
    {
        $act_code = rand(10000, 99999);

        $uar = new \DB\SQL\Mapper(\Base::instance()->get('DB'), 'user_activations');
        $uar->id_user = $user_id;
        $uar->activation_code = $act_code;
        $uar->account_verified = 1; // TODO: remove this when firebase validation is in place
        $uar->save();

        return $act_code;
    }

    public function createPasswordResetRecord(string $email, string $tdata): array
    {
        $reset_code = rand(10000, 99999);

        \Base::instance()->get('DB')->exec("delete from password_reset where email = ?", $email);

        $uar = new \DB\SQL\Mapper(\Base::instance()->get('DB'), 'password_reset');
        $uar->email = $email;
        $uar->token = md5(strval($reset_code) . $email . $tdata);
        $uar->sms_code = $reset_code;
        $uar->save();

        return ['token' => $uar->token, 'smscode' => $reset_code];
    }

    public function createEmailChangeRecord(string $newemail, string $oldemail): array
    {
        $reset_code = rand(10000, 99999);

        \Base::instance()->get('DB')->exec("delete from email_change where oldemail = ?", $oldemail);
        
        $uar = new \DB\SQL\Mapper(\Base::instance()->get('DB'), 'email_change');
        $uar->oldemail = $oldemail;
        $uar->newemail = $newemail;
        $uar->token = md5(strval($reset_code) . $oldemail . $newemail);
        $uar->sms_code = $reset_code;
        $uar->save();

        return ['token' => $uar->token, 'smscode' => $reset_code];
    }
}
