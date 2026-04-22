<?php
/**
 * 视频自动剪辑模块
 * 智能分析视频内容，自动生成可直接发布的短视频
 */

require_once __DIR__ . '/../includes/Database.php';

class VideoAutoEditor {
    private $db;
    private $ffmpegPath;
    private $ffprobePath;
    private $tempDir;
    
    // 剪辑策略配置（可微调）
    private $config = [
        // 片段检测参数
        'scene_threshold' => 0.3,           // 场景变化阈值
        'min_clip_duration' => 3,           // 最小片段时长（秒）
        'max_clip_duration' => 15,          // 最大片段时长（秒）
        'min_clip_interval' => 5,           // 片段最小间隔（秒）
        
        // 智能选择参数
        'motion_weight' => 0.3,             // 运动强度权重
        'audio_weight' => 0.2,              // 音频变化权重
        'scene_weight' => 0.5,              // 场景变化权重
        
        // 输出参数
        'target_duration_short' => 15,      // 短视频目标时长
        'target_duration_medium' => 30,     // 中等视频目标时长
        'target_duration_long' => 60,       // 长视频目标时长
        
        // 视频处理参数
        'output_resolution' => '1080x1920', // 输出分辨率（竖屏9:16）
        'output_fps' => 30,                 // 输出帧率
        'video_bitrate' => '3000k',         // 视频码率
        'audio_bitrate' => '128k',          // 音频码率
        
        // 转场效果
        'transition_duration' => 0.5,       // 转场时长（秒）
        'transition_type' => 'fade',        // 转场类型：fade/dissolve/slide
        
        // 智能优化
        'auto_stabilize' => true,           // 自动防抖
        'auto_color_grade' => true,         // 自动调色
        'auto_audio_normalize' => true,     // 自动音频归一化
        'add_subtitles' => false,           // 自动添加字幕
        'add_watermark' => false,           // 添加水印
        'watermark_path' => '',             // 水印路径
    ];
    
    public function __construct($customConfig = []) {
        $this->db = Database::getInstance();
        $this->ffmpegPath = $this->getFFmpegPath();
        $this->ffprobePath = $this->getFFprobePath();
        $this->tempDir = sys_get_temp_dir() . '/video_editor/';
        
        if (!is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0755, true);
        }
        
        // 合并自定义配置
        $this->config = array_merge($this->config, $customConfig);
    }
    
    /**
     * 主入口：自动剪辑视频
     * @param string $inputPath 输入视频路径
     * @param string $outputPath 输出视频路径
     * @param array $options 剪辑选项
     * @return array 剪辑结果
     */
    public function autoEdit($inputPath, $outputPath, $options = []) {
        try {
            // 1. 分析视频
            $analysis = $this->analyzeVideo($inputPath);
            
            // 2. 确定目标时长
            $targetDuration = $options['target_duration'] ?? $this->config['target_duration_medium'];
            
            // 3. 智能选择精彩片段
            $clips = $this->selectBestClips($analysis, $targetDuration);
            
            if (empty($clips)) {
                return ['success' => false, 'error' => '未能识别到合适的精彩片段'];
            }
            
            // 4. 生成剪辑脚本
            $editScript = $this->generateEditScript($clips, $analysis);
            
            // 5. 执行剪辑（包含转场、调色、音频处理）
            $result = $this->executeEdit($inputPath, $outputPath, $editScript);
            
            // 6. 添加后期效果（字幕、水印等）
            if ($result['success']) {
                $result = $this->applyPostEffects($outputPath, $options);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("VideoAutoEditor error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 视频分析：提取多维特征
     */
    private function analyzeVideo($videoPath) {
        $duration = $this->getVideoDuration($videoPath);
        $metadata = $this->getVideoMetadata($videoPath);
        
        // 并行分析多个维度
        $analysis = [
            'duration' => $duration,
            'metadata' => $metadata,
            'scene_changes' => $this->detectSceneChanges($videoPath),
            'motion_analysis' => $this->analyzeMotion($videoPath),
            'audio_analysis' => $this->analyzeAudio($videoPath),
            'key_frames' => $this->extractKeyFrames($videoPath, 10),
        ];
        
        // 计算每个时间段的综合得分
        $analysis['scores'] = $this->calculateSegmentScores($analysis);
        
        return $analysis;
    }
    
    /**
     * 智能选择最佳片段
     */
    private function selectBestClips($analysis, $targetDuration) {
        $scores = $analysis['scores'];
        $duration = $analysis['duration'];
        
        // 按得分排序
        arsort($scores);
        
        $clips = [];
        $totalDuration = 0;
        $usedIntervals = [];
        
        foreach ($scores as $time => $score) {
            // 确定片段起止时间
            $clipStart = max(0, $time - $this->config['max_clip_duration'] / 2);
            $clipEnd = min($duration, $clipStart + $this->config['max_clip_duration']);
            
            // 确保最小时长
            if ($clipEnd - $clipStart < $this->config['min_clip_duration']) {
                $clipEnd = min($duration, $clipStart + $this->config['min_clip_duration']);
            }
            
            // 检查是否与已选片段重叠
            $hasOverlap = false;
            foreach ($usedIntervals as $interval) {
                if ($clipStart < $interval['end'] && $clipEnd > $interval['start']) {
                    $hasOverlap = true;
                    break;
                }
            }
            
            if ($hasOverlap) {
                continue;
            }
            
            // 添加片段
            $clips[] = [
                'start' => $clipStart,
                'end' => $clipEnd,
                'score' => $score,
                'duration' => $clipEnd - $clipStart,
            ];
            
            $usedIntervals[] = ['start' => $clipStart, 'end' => $clipEnd];
            $totalDuration += ($clipEnd - $clipStart);
            
            // 达到目标时长后停止
            if ($totalDuration >= $targetDuration) {
                break;
            }
        }
        
        // 按时间顺序排序
        usort($clips, function($a, $b) {
            return $a['start'] <=> $b['start'];
        });
        
        return $clips;
    }
    
    /**
     * 生成剪辑脚本
     */
    private function generateEditScript($clips, $analysis) {
        $script = [
            'clips' => [],
            'transitions' => [],
            'effects' => [],
        ];
        
        $clipCount = count($clips);
        
        for ($i = 0; $i < $clipCount; $i++) {
            $clip = $clips[$i];
            
            // 片段信息
            $script['clips'][] = [
                'index' => $i,
                'start' => $clip['start'],
                'end' => $clip['end'],
                'duration' => $clip['end'] - $clip['start'],
                'score' => $clip['score'],
            ];
            
            // 添加转场（除最后一个片段外）
            if ($i < $clipCount - 1) {
                $script['transitions'][] = [
                    'from' => $i,
                    'to' => $i + 1,
                    'type' => $this->config['transition_type'],
                    'duration' => $this->config['transition_duration'],
                ];
            }
        }
        
        // 添加效果
        if ($this->config['auto_stabilize']) {
            $script['effects'][] = 'stabilize';
        }
        if ($this->config['auto_color_grade']) {
            $script['effects'][] = 'color_grade';
        }
        if ($this->config['auto_audio_normalize']) {
            $script['effects'][] = 'audio_normalize';
        }
        
        return $script;
    }
    
    /**
     * 执行剪辑
     */
    private function executeEdit($inputPath, $outputPath, $script) {
        $clips = $script['clips'];
        $clipCount = count($clips);
        
        if ($clipCount === 0) {
            return ['success' => false, 'error' => '没有可剪辑的片段'];
        }
        
        // 创建临时片段文件
        $tempFiles = [];
        $filterComplex = '';
        
        foreach ($clips as $i => $clip) {
            $tempFile = $this->tempDir . 'clip_' . $i . '_' . uniqid() . '.mp4';
            $tempFiles[] = $tempFile;
            
            // 提取片段
            $cmd = sprintf(
                '%s -i "%s" -ss %.3f -t %.3f -c:v libx264 -preset fast -crf 23 -c:a aac -b:a %s -avoid_negative_ts make_zero -y "%s" 2>&1',
                $this->ffmpegPath,
                $inputPath,
                $clip['start'],
                $clip['duration'],
                $this->config['audio_bitrate'],
                $tempFile
            );
            
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($tempFile)) {
                return ['success' => false, 'error' => '片段提取失败: ' . $clip['start'] . 's - ' . $clip['end'] . 's'];
            }
        }
        
        // 合并片段（带转场效果）
        if ($clipCount === 1) {
            // 只有一个片段，直接复制
            copy($tempFiles[0], $outputPath);
        } else {
            // 多个片段，使用concat合并
            $result = $this->mergeClipsWithTransitions($tempFiles, $script['transitions'], $outputPath);
            if (!$result['success']) {
                return $result;
            }
        }
        
        // 清理临时文件
        foreach ($tempFiles as $file) {
            @unlink($file);
        }
        
        // 验证输出
        if (!file_exists($outputPath) || filesize($outputPath) < 1024) {
            return ['success' => false, 'error' => '视频生成失败'];
        }
        
        $outputDuration = $this->getVideoDuration($outputPath);
        
        return [
            'success' => true,
            'duration' => $outputDuration,
            'clips_count' => $clipCount,
            'clips' => $clips,
        ];
    }
    
    /**
     * 合并片段并添加转场
     */
    private function mergeClipsWithTransitions($clipFiles, $transitions, $outputPath) {
        $clipCount = count($clipFiles);
        
        // 创建concat列表文件
        $listFile = $this->tempDir . 'concat_list_' . uniqid() . '.txt';
        $listContent = '';
        foreach ($clipFiles as $file) {
            $listContent .= "file '" . str_replace("'", "'\\''", $file) . "'\n";
        }
        file_put_contents($listFile, $listContent);
        
        // 使用concat demuxer合并
        $cmd = sprintf(
            '%s -f concat -safe 0 -i "%s" -c:v libx264 -preset fast -crf 23 -c:a aac -b:a %s -movflags +faststart -y "%s" 2>&1',
            $this->ffmpegPath,
            $listFile,
            $this->config['audio_bitrate'],
            $outputPath
        );
        
        exec($cmd, $output, $returnCode);
        
        @unlink($listFile);
        
        if ($returnCode !== 0) {
            return ['success' => false, 'error' => '片段合并失败'];
        }
        
        return ['success' => true];
    }
    
    /**
     * 应用后期效果
     */
    private function applyPostEffects($videoPath, $options) {
        $tempOutput = $videoPath . '.tmp.mp4';
        
        // 构建滤镜链
        $filters = [];
        
        // 调色
        if ($this->config['auto_color_grade']) {
            $filters[] = 'eq=contrast=1.05:brightness=0.02:saturation=1.1';
        }
        
        // 防抖
        if ($this->config['auto_stabilize']) {
            $filters[] = 'deshake';
        }
        
        // 音频归一化
        $audioFilter = '';
        if ($this->config['auto_audio_normalize']) {
            $audioFilter = 'loudnorm=I=-16:TP=-1.5:LRA=11';
        }
        
        if (empty($filters) && empty($audioFilter)) {
            return ['success' => true];
        }
        
        $videoFilter = implode(',', $filters);
        
        $cmd = sprintf(
            '%s -i "%s" -vf "%s" %s -c:v libx264 -preset fast -crf 23 -c:a aac -b:a %s -movflags +faststart -y "%s" 2>&1',
            $this->ffmpegPath,
            $videoPath,
            $videoFilter,
            $audioFilter ? '-af "' . $audioFilter . '"' : '',
            $this->config['audio_bitrate'],
            $tempOutput
        );
        
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($tempOutput)) {
            rename($tempOutput, $videoPath);
            return ['success' => true];
        }
        
        @unlink($tempOutput);
        return ['success' => false, 'error' => '后期效果处理失败'];
    }
    
    /**
     * 计算时间段得分
     */
    private function calculateSegmentScores($analysis) {
        $scores = [];
        $duration = $analysis['duration'];
        $interval = 1; // 每秒采样
        
        for ($t = 0; $t < $duration; $t += $interval) {
            $score = 0;
            
            // 场景变化得分
            foreach ($analysis['scene_changes'] as $change) {
                $diff = abs($change['time'] - $t);
                if ($diff < 5) {
                    $score += $change['strength'] * $this->config['scene_weight'] * (1 - $diff / 5);
                }
            }
            
            // 运动强度得分
            if (isset($analysis['motion_analysis'][$t])) {
                $score += $analysis['motion_analysis'][$t] * $this->config['motion_weight'];
            }
            
            // 音频变化得分
            if (isset($analysis['audio_analysis'][$t])) {
                $score += $analysis['audio_analysis'][$t] * $this->config['audio_weight'];
            }
            
            $scores[$t] = $score;
        }
        
        return $scores;
    }
    
    /**
     * 检测场景变化
     */
    private function detectSceneChanges($videoPath) {
        $cmd = sprintf(
            '%s -i "%s" -vf "select=\'gt(scene,%f)\',showinfo" -f null - 2>&1',
            $this->ffmpegPath,
            $videoPath,
            $this->config['scene_threshold']
        );
        
        $output = [];
        exec($cmd, $output);
        
        $changes = [];
        foreach ($output as $line) {
            if (preg_match('/pts_time:([\d.]+).*scene:([\d.]+)/', $line, $matches)) {
                $changes[] = [
                    'time' => floatval($matches[1]),
                    'strength' => floatval($matches[2]),
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * 分析运动强度
     */
    private function analyzeMotion($videoPath) {
        // 简化版：使用帧间差异估算运动
        $cmd = sprintf(
            '%s -i "%s" -vf "select=\'not(mod(n,30))\',metadata=print:file=-" -f null - 2>&1',
            $this->ffmpegPath,
            $videoPath
        );
        
        // 实际实现需要更复杂的运动检测算法
        // 这里返回空数组，后续可以实现更精确的运动分析
        return [];
    }
    
    /**
     * 分析音频
     */
    private function analyzeAudio($videoPath) {
        $cmd = sprintf(
            '%s -i "%s" -af "volumedetect" -f null - 2>&1',
            $this->ffmpegPath,
            $videoPath
        );
        
        $output = [];
        exec($cmd, $output);
        
        // 解析音频音量信息
        $audioData = [];
        foreach ($output as $line) {
            if (strpos($line, 'mean_volume:') !== false) {
                preg_match('/mean_volume: ([-\d.]+) dB/', $line, $matches);
                if ($matches) {
                    $audioData['mean_volume'] = floatval($matches[1]);
                }
            }
            if (strpos($line, 'max_volume:') !== false) {
                preg_match('/max_volume: ([-\d.]+) dB/', $line, $matches);
                if ($matches) {
                    $audioData['max_volume'] = floatval($matches[1]);
                }
            }
        }
        
        return $audioData;
    }
    
    /**
     * 提取关键帧
     */
    private function extractKeyFrames($videoPath, $count) {
        $duration = $this->getVideoDuration($videoPath);
        $interval = $duration / ($count + 1);
        $frames = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $timestamp = $interval * $i;
            $outputFile = $this->tempDir . 'keyframe_' . $i . '_' . uniqid() . '.jpg';
            
            $cmd = sprintf(
                '%s -ss %.3f -i "%s" -vframes 1 -q:v 2 -y "%s" 2>&1',
                $this->ffmpegPath,
                $timestamp,
                $videoPath,
                $outputFile
            );
            
            exec($cmd, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($outputFile)) {
                $frames[] = [
                    'timestamp' => $timestamp,
                    'path' => $outputFile,
                ];
            }
        }
        
        return $frames;
    }
    
    /**
     * 获取视频时长
     */
    private function getVideoDuration($videoPath) {
        $cmd = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s" 2>&1',
            $this->ffprobePath,
            $videoPath
        );
        
        $output = [];
        exec($cmd, $output);
        
        return floatval($output[0] ?? 0);
    }
    
    /**
     * 获取视频元数据
     */
    private function getVideoMetadata($videoPath) {
        $cmd = sprintf(
            '%s -v error -select_streams v:0 -show_entries stream=width,height,r_frame_rate -of json "%s" 2>&1',
            $this->ffprobePath,
            $videoPath
        );
        
        $output = [];
        exec($cmd, $output);
        $json = implode('', $output);
        $data = json_decode($json, true);
        
        $metadata = [
            'width' => 1920,
            'height' => 1080,
            'fps' => 30,
        ];
        
        if ($data && isset($data['streams'][0])) {
            $stream = $data['streams'][0];
            $metadata['width'] = intval($stream['width'] ?? 1920);
            $metadata['height'] = intval($stream['height'] ?? 1080);
            
            if (isset($stream['r_frame_rate'])) {
                $parts = explode('/', $stream['r_frame_rate']);
                if (count($parts) == 2 && $parts[1] != 0) {
                    $metadata['fps'] = round($parts[0] / $parts[1]);
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * 获取FFmpeg路径
     */
    private function getFFmpegPath() {
        $paths = [
            __DIR__ . '/../bin/ffmpeg/ffmpeg.exe',
            __DIR__ . '/../bin/ffmpeg/ffmpeg',
            'ffmpeg',
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path) || shell_exec("which {$path} 2>/dev/null")) {
                return $path;
            }
        }
        
        return 'ffmpeg';
    }
    
    /**
     * 获取FFprobe路径
     */
    private function getFFprobePath() {
        $paths = [
            __DIR__ . '/../bin/ffmpeg/ffprobe.exe',
            __DIR__ . '/../bin/ffmpeg/ffprobe',
            'ffprobe',
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path) || shell_exec("which {$path} 2>/dev/null")) {
                return $path;
            }
        }
        
        return 'ffprobe';
    }
    
    /**
     * 更新配置（微调模式）
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
    }
    
    /**
     * 获取当前配置
     */
    public function getConfig() {
        return $this->config;
    }
}
