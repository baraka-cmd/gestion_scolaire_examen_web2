<?php
echo password_hash('Password123!', PASSWORD_BCRYPT, ['cost' => 10]);