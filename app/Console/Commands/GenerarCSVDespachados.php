<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class GenerarCSVDespachados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reportes:generar {tipo_reporte} {id_periodo} {--formato=csv} {--output=} {--test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar reportes CSV o Excel ejecutando procedimientos almacenados';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tipoReporte = $this->argument('tipo_reporte');
        $idPeriodo = $this->argument('id_periodo');
        $outputPath = $this->option('output') ?: storage_path('app/reportes/');
        $isTest = $this->option('test');

        // Validar tipo de reporte
        $tiposValidos = ['despachados', 'pedidos_alcances', 'liquidados', 'devoluciones', 'ventas'];
        if (!in_array($tipoReporte, $tiposValidos)) {
            $this->error("Tipo de reporte inválido. Tipos válidos: " . implode(', ', $tiposValidos));
            return 1;
        }

        // Validar período
        $periodo = DB::table('periodoescolar')
            ->where('idperiodoescolar', $idPeriodo)
            ->first();

        if (!$periodo) {
            $this->error("El período $idPeriodo no existe");
            return 1;
        }

        $this->info("🚀 Iniciando generación de reporte: $tipoReporte para período $idPeriodo");

        try {
            // Determinar procedimiento
            $procedimiento = $this->determinarProcedimiento($tipoReporte, $idPeriodo);
            
            if (!$procedimiento) {
                $this->error("No se pudo determinar el procedimiento para $tipoReporte");
                return 1;
            }

            $this->info("📋 Procedimiento a ejecutar: {$procedimiento['sp']}({$procedimiento['periodo']})");

            // Configurar timeout y memoria
            set_time_limit(0);
            ini_set('memory_limit', '4G');

            // Crear directorio si no existe
            if (!is_dir($outputPath)) {
                mkdir($outputPath, 0755, true);
            }

            $filename = $tipoReporte . '_' . $idPeriodo . '_' . date('Y-m-d_H-i-s') . '.csv';
            $fullPath = $outputPath . $filename;

            $startTime = microtime(true);

            // Abrir archivo para escritura
            $file = fopen($fullPath, 'w');
            
            if (!$file) {
                $this->error("No se pudo crear el archivo: $fullPath");
                return 1;
            }

            // BOM para UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Ejecutar procedimiento
            $pdo = DB::getPdo();
            $stmt = $pdo->prepare("CALL {$procedimiento['sp']}(?)");
            $result = $stmt->execute([$procedimiento['periodo']]);

            if (!$result) {
                $this->error("Error al ejecutar procedimiento: " . implode(', ', $stmt->errorInfo()));
                fclose($file);
                unlink($fullPath);
                return 1;
            }

            $headerWritten = false;
            $rowCount = 0;
            $progressBar = $this->output->createProgressBar(100000); // Estimado inicial

            $this->info("\n📊 Procesando datos...");

            // Procesar datos
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Escribir header en la primera fila
                if (!$headerWritten) {
                    fputcsv($file, array_keys($row));
                    $headerWritten = true;
                }

                // Escribir datos
                fputcsv($file, array_values($row));
                $rowCount++;

                // Actualizar progress bar cada 1000 registros
                if ($rowCount % 1000 === 0) {
                    $progressBar->advance(1000);
                }

                // Para modo test, solo procesar primeros 100 registros
                if ($isTest && $rowCount >= 100) {
                    $this->info("\n⚠️  Modo TEST: Solo procesando primeros 100 registros");
                    break;
                }
            }

            $progressBar->finish();
            fclose($file);

            $totalTime = round(microtime(true) - $startTime, 2);
            $fileSize = round(filesize($fullPath) / 1024 / 1024, 2); // MB

            $this->info("\n✅ Reporte generado exitosamente!");
            $this->info("📁 Archivo: $fullPath");
            $this->info("📊 Registros procesados: " . number_format($rowCount));
            $this->info("⏱️  Tiempo de ejecución: {$totalTime}s");
            $this->info("💾 Tamaño del archivo: {$fileSize} MB");

            if ($isTest) {
                $this->info("🧪 Modo TEST activado - Solo primeros 100 registros");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error durante la generación: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Determinar qué procedimiento almacenado usar según el tipo de reporte y período
     */
    private function determinarProcedimiento($tipo_reporte, $id_periodo)
    {
        switch ($tipo_reporte) {
            case 'despachados':
                return [
                    'sp' => 'sp_despachados',
                    'periodo' => $id_periodo
                ];
                
            case 'pedidos_alcances':
                // Para períodos > 27 usa sp_pedidos_alcances_new
                // Para períodos ≤ 26 usa sp_pedidos_alcances_old
                if ($id_periodo > 27) {
                    return [
                        'sp' => 'sp_pedidos_alcances_new',
                        'periodo' => $id_periodo
                    ];
                } else {
                    return [
                        'sp' => 'sp_pedidos_alcances_old',
                        'periodo' => $id_periodo
                    ];
                }
                
            case 'liquidados':
                return [
                    'sp' => 'sp_liquidados',
                    'periodo' => $id_periodo
                ];
                
            case 'devoluciones':
                return [
                    'sp' => 'sp_devoluciones',
                    'periodo' => $id_periodo
                ];
                
            case 'ventas':
                return [
                    'sp' => 'sp_ventas',
                    'periodo' => $id_periodo
                ];
                
            default:
                return null;
        }
    }
}
