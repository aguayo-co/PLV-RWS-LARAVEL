<?php

namespace App\Console\Commands;

use App\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ProcessChilexpressTracking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:chilexpress-tracking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Chilexpress tracking information and update Sales.';

    protected $connection;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    const COL_COD_EVENT = 3;
    const COL_EVENT = 4;
    const COL_DATE = 5;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $server = env('CHILEXPRESS_FTP_SERVER');
        $username = env('CHILEXPRESS_FTP_USER');
        $password = env('CHILEXPRESS_FTP_PASS');
        $this->connection = @ftp_connect($server);

        if (!$this->connection) {
            $this->error('Could not connect to FTP server.');
            return;
        }

        if (!@ftp_login($this->connection, $username, $password)) {
            $this->error('Could not login to FTP server.');
            return;
        }
        ftp_pasv($this->connection, true);

        $files = $this->getTrackingFilesList();
        foreach ($files as $file) {
            $salesEvents = $this->getEvents($file);
            $this->updateSales($salesEvents);
            $this->archiveFile($file);
        }
    }

    /**
     * Get a list of all the tracking files available in the ftp server.
     */
    protected function getTrackingFilesList()
    {
        $path = App::environment('production') ? 'files' : 'test-files';
        $fileList = ftp_nlist($this->connection, $path);
        $files = [];
        foreach ($fileList as $file) {
            $matched = preg_match('/files\/PRILOV_[0-9]{12}\.csv$/', $file);
            if ($matched) {
                $files[] = $file;
            }
        }
        return $files;
    }

    /**
     * Get tracking file from FTP server and return rows per sale.
     */
    protected function getEvents($file)
    {
        $tempFile = tmpfile();
        ftp_fget($this->connection, $tempFile, $file, FTP_ASCII);
        fseek($tempFile, 0);

        $salesEvents = collect();

        while (($data = fgetcsv($tempFile, 0, ";")) !== false) {
            $matches = collect();
            $refBase = config('prilov.chilexpress.referencia_base');
            // Patter to match: /{refBase}0000-0000
            $pattern = '/' . $refBase . '[0-9]+-(?P<saleId>[0-9]+)/';
            if (preg_match($pattern, array_get($data, 1), $matches)) {
                $saleId = $matches['saleId'];
                if (!$salesEvents->has($saleId)) {
                    $salesEvents->put($saleId, collect());
                }
                $salesEvents->get($saleId)->push(collect($data));
            }
        }
        fclose($tempFile);

        return $salesEvents;
    }

    /**
     * Update Sales using tracking information.
     */
    protected function updateSales($salesEvents)
    {
        $sales = Sale::whereIn('id', $salesEvents->keys())
            ->whereBetween('status', [Sale::STATUS_PAYED, Sale::STATUS_SHIPPED])->get();

        foreach ($sales as $sale) {
            $this->updateSale($sale, $salesEvents->get($sale->id));
        }
    }

    /**
     * Update sale status based on events from Chilexpress.
     *
     * Keep track of Chilexpress having the packaged (shipped),
     * and if it was correctly delivered.
     *
     * Any other status will be ignored.
     */
    protected function updateSale($sale, $events)
    {
        // Does chilexpress have the package?
        $shipped = $events
            ->where(ProcessChilexpressTracking::COL_COD_EVENT, 4)
            ->isNotEmpty();

        if ($shipped && $sale->status < Sale::STATUS_SHIPPED) {
            $sale->status = Sale::STATUS_SHIPPED;
        }

        // Has the package been delivered?
        // Package is delivered when EVENT CODE is 14 and TEXT is similar to:
        // - PIEZA ENTREGADA DESTINATARIO
        // But that text can change, so we just look for the word
        // `destinatario`, which should be enough for us.
        $delivered = $events
            ->where(ProcessChilexpressTracking::COL_COD_EVENT, 14)
            ->filter(function ($row) {
                $event = strtolower($row->get(ProcessChilexpressTracking::COL_EVENT));
                return strpos($event, 'destinatario') !== false;
            })->isNotEmpty();

        if ($delivered && $sale->status < Sale::STATUS_DELIVERED) {
            $sale->status = Sale::STATUS_DELIVERED;
        }

        // Persist changes.
        if ($sale->isDirty()) {
            $sale->save();
        }
    }

    /**
     * Archive processed tracking file.
     */
    protected function archiveFile($file)
    {
        $archivePath = App::environment('production') ? 'archived/' : 'test-archived/';
        $fileParts = explode('/', $file);
        ftp_rename($this->connection, $file, $archivePath . end($fileParts));
    }
}
