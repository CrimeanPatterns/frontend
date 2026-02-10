<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

/**
 * Authentication check in UserManager creates master sub-request to check_path defined in firewall.
 * Therefore for some reason session id set up more than one time and default MockFileSessionStorage throws LogicException in this case.
 * Override setId default behavior for now.
 *
 * TODO: debug sub-request session handling and fix it to get rid of this workaround
 */
class TestMockSessionStorage extends MockFileSessionStorage
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
