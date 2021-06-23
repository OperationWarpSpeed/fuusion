<?php

/**
 * Lock/Unlock SSH password authentication
 * @author Mihajlo 28.sep.2016
 * @copyright SoftNAS, Inc.
 */

echo "Setting SSHD... ";

if(isset($_REQUEST['unlock'])){
        exec('sudo sed "s/^ *PasswordAuthentication .*/PasswordAuthentication yes/i" -i /etc/ssh/sshd_config');
        exec('sudo sed "s/^ *PasswordAuthentication .*/PasswordAuthentication yes/i" -i /etc/ssh/sshd_config.hpn');
        exec('sudo sed "s/^ *PermitRootLogin .*/PermitRootLogin yes/i" -i /etc/ssh/sshd_config');
        exec('sudo sed "s/^ *PermitRootLogin .*/PermitRootLogin yes/i" -i /etc/ssh/sshd_config.hpn');
        echo "(openinig)</br></br>";
}else{
          exec('sudo sed "s/^ *PasswordAuthentication .*/PasswordAuthentication no/i" -i /etc/ssh/sshd_config');
          exec('sudo sed "s/^ *PasswordAuthentication .*/PasswordAuthentication no/i" -i /etc/ssh/sshd_config.hpn');
          exec('sudo sed "s/^ *PermitRootLogin .*/PermitRootLogin without-password/i" -i /etc/ssh/sshd_config');
          exec('sudo sed "s/^ *PermitRootLogin .*/PermitRootLogin without-password/i" -i /etc/ssh/sshd_config.hpn');
        echo "(closing)</br></br>";

}

exec('sudo service sshd restart');
echo "Done</br></br>";

?>