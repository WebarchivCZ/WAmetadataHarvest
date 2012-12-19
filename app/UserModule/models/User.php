<?php

class User extends Model\Base {

    protected $output = array(
        'created_at' => self::OUTPUT_TRANSFORM_DATETIME,
    );

    protected $outputFilter = array(
        self::OUTPUT_FORMAT_SHORT => array(
            'id',
            'username',
        ),
    );

    public function getDataFromRegistration($values)
    {
        $data = array(
            'username' => $values['username'],
            'password' => $this->getHashedPassword($values['password']),
            'email' => $values['email'],
            'tries' => 0,
            'last_login' => NULL,
            'last_try' => NULL,
        );
        return $data;
    }

    public function isPasswordMatching($user, $password)
    {
        return $user->password === $this->getHashedPassword($password, $user->password);
    }

    public function getHashedPassword($password, $hash = NULL)
    {
        if (NULL === $hash) {
            $hash = $this->generateHash();
        }
        return crypt($password, $hash);
    }

    public function setPassword($user, $password)
    {
        $user->password = $this->getHashedPassword($password);
        $user->update();
    }

    public function hasPassword($user)
    {
        return (bool) $user->password;
    }

    /**
     * Generate hash for CRYPT_SHA512
     *
     * @return string hash ready to be used in crypt
     */
    public function generateHash()
    {
        $rounds = 10000;
        $hash = substr(md5(uniqid(mt_rand(), TRUE)), 8, 16);
        return '$6$rounds=' . $rounds . '$' . $hash . '$';
    }

    /**
     * Compute when user should try login again (prevent bruteforce attacks)
     * @return DateTime Next available time
     */
    public function shouldTryLoginAfter($user)
    {
        $stopAt = 15; // 4h 33m 4s
        $after = $user->last_try ?: new DateTime;
        if ($user->last_try > $user->last_login) {
            $tries = min($user->tries, $stopAt);
            $seconds = (pow(2, $tries) - 1) / 2;
            $interval = new DateInterval('PT' . (int) $seconds . 'S');
            $after->add($interval);
        }
        return $after;
    }

    public function registerInvalidTry($user)
    {
        $user->tries++;
        $user->last_try = new DateTime;
        $user->update();
    }

    public function registerLogin($user)
    {
        $user->tries = 0;
        $user->last_try = new DateTime;
        $user->last_login = new DateTime;
        $user->update();
    }

    public function getByUsername($username)
    {
        return $this->getTable()->where('UPPER(`username`) LIKE UPPER(?)', $username)->limit(1)->fetch();
    }

    public function getByEmail($email)
    {
        return $this->getTable()->where('UPPER(`email`) = UPPER(?)', $email)->limit(1)->fetch();
    }

    public function getSettings($user)
    {
        return json_decode($user->settings, JSON_FORCE_OBJECT);
    }

    public function setSettings($user, $settings)
    {
        $user->settings = json_encode((object) $settings);
        $user->update();
    }

}
