@servers(['web' => ['bfh@mahout.ti.bfh.ch']])

@task('deploy', ['on' => 'web'])
    cd /var/www/mahout.ti.bfh.ch/
    {{--  
        Terminate ant running horizon tasks. It will be 
        restarted automatically by the Supervisor.
    --}}
    php artisan horizon:terminate
    
    git pull origin master
    composer install --no-dev
@endtask

@task('writetest', ['on' => 'web'])
      echo "🏃  Starting configuring files."
      touch test.txt
      echo "test write to file." >> test.txt
      echo "🚀   Files should be written..."
@endtask
