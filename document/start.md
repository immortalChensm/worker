### 启动分析  

- 测试源码    
```php  
<?php
/**
 * Created by PhpStorm.
 * User: 1655664358@qq.com
 * Date: 2019/7/10
 * Time: 21:53
 */

require_once 'vendor/autoload.php';

$worker = new \Workerman\Worker("http://127.0.0.1:1234");
$worker->count=4;

$worker->onMessage=function ($connection,$data){
    print_r($_POST);
    $connection->send("hello,world");
};

\Workerman\Worker::runAll();
```  

- 构造函数分析     
本类已经引入require_once __DIR__ . '/Lib/Constants.php';  
一些常量定义文件,后面它会引用到
 ```php  
  public function __construct($socket_name = '', $context_option = array())
     {
         // Save all worker instances.
         //生成唯一的hash值
         $this->workerId                    = spl_object_hash($this);
         static::$_workers[$this->workerId] = $this;
         static::$_pidMap[$this->workerId]  = array();
 
         // Get autoload root path.
         //得到产生一条回溯跟踪
         //https://www.php.net/manual/zh/function.debug-backtrace.php 文档说明
         $backtrace                = debug_backtrace();
         //得到运行的根目录 
         $this->_autoloadRootPath = dirname($backtrace[0]['file']);
 
         // Context for socket.
         //协议名称
         if ($socket_name) {
             $this->_socketName = $socket_name;
             //流配置选项
             //tcp协议是基于字节流的传输，采用应答机制 
             //低层可通过调用set_socket_opt选项【c语言】控制
             //也可以修改内核配置[linux内核] 
             //从而控制文件描述符的属性 
             //上层协议可以是http,ws,https,这些协议
             if (!isset($context_option['socket']['backlog'])) {
                 $context_option['socket']['backlog'] = static::DEFAULT_BACKLOG;
             }
             //创建一个流  
             //流的概念：
             //流就是一种数据，它的来源有文件【文件流】，网络【如socket】，硬盘，键盘等硬件
             //文件流：数据从文件【源】加载到内存的过程称为输入流 
             //数据从内存写入文件的过程叫做输出流
             //数据在数据源和内存【程序】之间进行传输的过程就叫数据流DATA STREAM
             //数据从数据源码加载到内存【程序】叫做【如给变量赋值】叫做输入流INPUT STREAM
             //数据从内存【程序】流向数据源码的过程叫做输出流OUTPUT STREAM 
             //最好有点编程经验，不然听不懂流概念我也没办法救你了^_^  
             
             //所以网络【tcp字节流，要不要去看看tcp和udp的区别？】
             //或是去了解下tcp/ip协议？可以去阅读本人在laravel-china社区编写过的内容 
             //天天撸api接口，天天操文件，天天操键盘，操鼠标，看片【应该了解流吧^_^】
             
             $this->_context = stream_context_create($context_option);
         }
     }
 ```  
 
- 给onMessage传递一个匿名函数    
  public $onMessage = null;
 ```php  
 $worker->onMessage=function ($connection,$data){
     print_r($_POST);
     $connection->send("hello,world");
 };
 ```   
 
- run运行  
```php  
public static function runAll()
    {
        static::checkSapiEnv();
        static::init();
        static::lock();
        static::parseCommand();
        static::daemonize();
        static::initWorkers();
        static::installSignal();
        static::saveMasterPid();
        static::unlock();
        static::displayUI();
        static::forkWorkers();
        static::resetStd();
        static::monitorWorkers();
    }
```  

   -  static::checkSapiEnv();分析    
   源码  
   ```php  
   protected static function checkSapiEnv()
       {
           // Only for cli.
           if (php_sapi_name() != "cli") {
               exit("only run in command line mode \n");
           }
           if (DIRECTORY_SEPARATOR === '\\') {
               self::$_OS = OS_TYPE_WINDOWS;
           }
       }
   ```  
   得到php的运行模式 
   文档[运行模式](https://www.php.net/manual/zh/function.php-sapi-name.php)  
   这没啥可以说的吧,后面那常量就是常量定义文件里的东西 
   `define('OS_TYPE_WINDOWS', 'windows');`   
   
   -  static::init();  
   ```php  
   protected static function init()
       {
       //这个是设置自定义错误处理方法
       //作为phper码农不可能没用过，除非你看片了
           set_error_handler(function($code, $msg, $file, $line){
               Worker::safeEcho("$msg in file $file on line $line\n");
           });
   
           // Start file.  
           //这个不用说了吧，打印一条信息【追溯】
           $backtrace        = debug_backtrace(); 
           //得到运行的文件名称【别说看不懂】
           static::$_startFile = $backtrace[count($backtrace) - 1]['file'];
   
            //把\【linux】/[windows】替换为_线
           $unique_prefix = str_replace('/', '_', static::$_startFile);
   
           // Pid file. 
           //进程pid文件
           if (empty(static::$pidFile)) {
               static::$pidFile = __DIR__ . "/../$unique_prefix.pid";
           }
   
           // Log file.
           if (empty(static::$logFile)) {
               static::$logFile = __DIR__ . '/../workerman.log';
           }
           $log_file = (string)static::$logFile;
           if (!is_file($log_file)) {
               touch($log_file);
               chmod($log_file, 0622);
           }
   
           // State.
           static::$_status = static::STATUS_STARTING;
   
           // For statistics.
           static::$_globalStatistics['start_timestamp'] = time();
           static::$_statisticsFile                      = sys_get_temp_dir() . "/$unique_prefix.status";
   
           // Process title.
           static::setProcessTitle('WorkerMan: master process  start_file=' . static::$_startFile);
   
           // Init data for worker id.
           static::initId();
   
           // Timer init.
           Timer::init();
       }

   ```  
   看看它的打印显示函数  
   ```php  
    public static function safeEcho($msg, $decorated = false)
       {
       //这地方怎么，回事啊，看下面的解释
           $stream = static::outputStream();
           if (!$stream) {
               return false;
           }
           //颜色处理【具体去看linux的终端处理，不要跟我说不会百度】
           if (!$decorated) {
               $line = $white = $green = $end = '';
               if (static::$_outputDecorated) {
                   $line = "\033[1A\n\033[K";
                   $white = "\033[47;30m";
                   $green = "\033[32;40m";
                   $end = "\033[0m";
               }
               $msg = str_replace(array('<n>', '<w>', '<g>'), array($line, $white, $green), $msg);
               $msg = str_replace(array('</n>', '</w>', '</g>'), $end, $msg);
           } elseif (!static::$_outputDecorated) {
               return false;
           }
           //数据从内存【php程序】流向数据源【屏幕终端】
           fwrite($stream, $msg);
           //清空输出
           fflush($stream);
           return true;
       }
   ```   
   
   再继续  
   ```php  
   private static function outputStream($stream = null)
       {
           if (!$stream) {
           //STDOUT是个什么东西啊？
           //这里就要说一下linux中所有的硬件全映射成文件了【为什么？自己去想】
           //输入设备：如键盘，鼠标等映射为stdin/STDIN
           //输出设备：如显示器，映射为stdout/STDOUT，stderr/STDERR   
           //打印机：stdprn一般我们不用  
           //这些文件一般映射到对应的硬件设备【只要操作一下内存中的数据会流向这些设备】
           //所以你应该听过输出缓冲区，输入缓冲区吧  
           //要不你去翻一下你大学时学c时的概念呗
               $stream = static::$_outputStream ? static::$_outputStream : STDOUT;
           }
           if (!$stream || !is_resource($stream) || 'stream' !== get_resource_type($stream)) {
               return false;
           }
           
           $stat = fstat($stream);  
           
           //检测是不是普通文件---具体解释看下面stat的解释  
           //看不懂？那回家放牛吧
           if (($stat['mode'] & 0170000) === 0100000) {
               // file
               static::$_outputDecorated = false;
           } else {
               static::$_outputDecorated =
                   static::$_OS === OS_TYPE_LINUX &&
                   function_exists('posix_isatty') &&
                   posix_isatty($stream);  
                   
                   //https://www.php.net/manual/zh/function.posix-isatty.php  
                   //如果STDOUT不是常规的文件 
                   //static::$_outputDecorated 
                   //static::$_OS 
                   //判断STDOUT是否是可交互式终端【就是终端能否输入东西进入交互模式】  
                   
           }
           //返回STDOUT 交互式的输出终端流【输出流】
           return static::$_outputStream = $stream;
       }

   ```  
   fstat【stat访问文件详细信息】用于访问文件接口的函数，返回文件的相关信息，基本返回如下内容   
   [fstat函数说明](https://www.php.net/manual/zh/function.stat.php)
   ```php  
   Array
   (
       [0] => 1
       [1] => 0
       [2] => 4096
       [3] => 1
       [4] => 0
       [5] => 0
       [6] => 1
       [7] => 0
       [8] => 0
       [9] => 0
       [10] => 0
       [11] => -1
       [12] => -1
       [dev] => 1//所在的设备标识
       [ino] => 0//文件结点号
       [mode] => 4096//文件保护模式
       [nlink] => 1//硬连接数
       [uid] => 0//文件用户标识
       [gid] => 0//组标识
       [rdev] => 1//文件所表示的特殊设备文件的设备标识
       [size] => 0//文件大小
       [atime] => 0//最后访问时间
       [mtime] => 0//最后修改时间
       [ctime] => 0//最后状态改变时间
       [blksize] => -1//文件系统的块大小
       [blocks] => -1//分配给文件的块数量
   )
   ```  
   下面重点说一下这个stat函数的结构体  
   ```c  
   struct stat
   {
       dev_t st_dev; //device 文件的设备编号
       ino_t st_ino; //inode 文件的i-node
       mode_t st_mode; //protection 文件的类型和存取的权限
       nlink_t st_nlink; //number of hard links 连到该文件的硬连接数目, 刚建立的文件值为1.
       uid_t st_uid; //user ID of owner 文件所有者的用户识别码
       gid_t st_gid; //group ID of owner 文件所有者的组识别码
       dev_t st_rdev; //device type 若此文件为装置设备文件, 则为其设备编号
       off_t st_size; //total size, in bytes 文件大小, 以字节计算
       unsigned long st_blksize; //blocksize for filesystem I/O 文件系统的I/O 缓冲区大小.
       unsigned long st_blocks; //number of blocks allocated 占用文件区块的个数, 每一区块大小为512 个字节.
       time_t st_atime; //time of lastaccess 文件最近一次被存取或被执行的时间, 一般只有在用mknod、utime、read、write 与tructate 时改变.
       time_t st_mtime; //time of last modification 文件最后一次被修改的时间, 一般只有在用mknod、utime 和write 时才会改变
       time_t st_ctime; //time of last change i-node 最近一次被更改的时间, 此参数会在文件所有者、组、权限被更改时更新
   };
   ```    
   
   先前所描述的st_mode 则定义了下列数种情况：    
   1、S_IFMT 0170000 文件类型的位遮罩    
   2、S_IFSOCK 0140000 scoket   
   3、S_IFLNK 0120000 符号连接   
   4、S_IFREG 0100000 一般文件   
   5、S_IFBLK 0060000 区块装置   
   6、S_IFDIR 0040000 目录   
   7、S_IFCHR 0020000 字符装置   
   8、S_IFIFO 0010000 先进先出   
   9、S_ISUID 04000 文件的 (set user-id on execution)位   
   10、S_ISGID 02000 文件的 (set group-id on execution)位   
   11、S_ISVTX 01000 文件的sticky 位   
   12、S_IRUSR (S_IREAD) 00400 文件所有者具可读取权限   
   13、S_IWUSR (S_IWRITE)00200 文件所有者具可写入权限   
   14、S_IXUSR (S_IEXEC) 00100 文件所有者具可执行权限   
   15、S_IRGRP 00040 用户组具可读取权限   
   16、S_IWGRP 00020 用户组具可写入权限   
   17、S_IXGRP 00010 用户组具可执行权限   
   18、S_IROTH 00004 其他用户具可读取权限   
   19、S_IWOTH 00002 其他用户具可写入权限  
   20、S_IXOTH 00001 其他用户具可执行权限上述的文件类型在 POSIX 中定义了检查这些类型的宏定义     
   21、S_ISLNK (st_mode) 判断是否为符号连接    
   22、S_ISREG (st_mode) 是否为一般文件    
   23、S_ISDIR (st_mode) 是否为目录   
   24、S_ISCHR (st_mode) 是否为字符装置文件   
   25、S_ISBLK (s3e) 是否为先进先出   
   26、S_ISSOCK (st_mode)     
   
   说一下PHP的数字输出：  
   php打印时【管你用echo,print这些函数】它们的输出进制类型是10进制！！！  
   
   但是mode【是八进制哦】【别说你不会进制，要不去学一下数字电路吧？不学怪我喽】  
   0170000是文件类型的位遮罩，通过和mode与运算得出文件类型，它本身呢是个权限数字  
   
   linux【ls命令熟悉吧，用于查看文件的权限】 
   r=4,w=2,x=1  
   
   所以呢你最好去测试一下如下代码  
   ```php  
   $file = "demo.php";
   
   $fobj = fopen($file,"r");
   $stat = fstat($fobj);
   
   fclose($fobj);
   ```  
   它的输出结果是33206【输出的是10进制！！！】转换为二进制后得  
   001 000 011 011 011     
   转换为八进制后得   
   10666【当然你最好去linux上测试好吗，别动不动拿垃圾win折腾，ok?】  
   666权限是什么【rwx=7,rw=6】就是具有读写能力【属主，组，其它】的权限值  
   
   0170000 【是八进制哦】【八进制以0开头，十六进制以0X开头，二进制以0b开头】  
   转换为二进制是   
   001 111 000 000 000  000  
   
   二进制相与运算结果是:  【要不再去学学二进制的加法，减少，与，或，非运算吧^_^】   
   001 000 000 000 000   
   转换为八进制是  
   100000  S_IFREG 0100000 一般文件      
   转换为十进制是【php输出的是十进制】  
   32768 【建议你在linux上测试，免得你真看不懂我在写什么飞机】    
   
   至此解释完毕【如果没有看请去看php官方文档的函数说明，如果还看不懂建议去linux玩一下stat命令】  
   还看不懂【？那我没有办法了】   
   
   
   
     
   