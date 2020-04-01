<?php

class Worker
{

    static $address = [];

    public function run()
    {


        self::$address[0] = "china";

        echo "start pid=" . posix_getpid() . "\n";
        $pid = pcntl_fork();

        if ($pid == 0) {
            self::$address[1] = "japanese";
            $i                = 0;
            while (1) {
                sprintf(STDOUT, "[tom] child process %d=%d,i=%d=%s\n", posix_getpid(), posix_getgid(), $i, self::$address[$i]);
                $i++;
                sleep(2);
            }
            exit(0);

        }

        posix_setgpid($pid, posix_getpid());

        $pid = pcntl_fork();
        if ($pid == 0) {
            {
                posix_setgid(posix_getpid());

                $i                = 0;
                self::$address[2] = "amercian";
                while (1) {
                    sprintf(STDOUT, "[tony] child process %d=%d,i=%d=%s\n", posix_getpid(), posix_getgid(), $i, self::$address[$i]);
                    $i++;
                    sleep(2);
                }
                exit(0);
            }


            $pid = pcntl_wait();

            echo "end %d" . posix_getpid();
            //posix_setgid();
        }
    }
}

$worker = new Worker();

$worker->run();
