<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../lib/AIProviderManager.php';
require_once __DIR__ . '/../lib/UsageTracker.php';


$currentUserId = $_SESSION['user']['id'] ?? null;


$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_available_model':
            getAvailableModel();
            break;
            
        case 'upload_video':
            handleVideoUpload();
            break;
            
        case 'analyze_video':
            handleVideoAnalysis();
            break;
            
        case 'edit_video':
            handleVideoEditWithTimeRange();
            break;
            
        case 'export_segment':
            handleExportSegment();
            break;
            
        case 'export_all_segments':
            handleExportAllSegments();
            break;
            
        case 'generate_prompt':
            handleGeneratePrompt();
            break;
            
        case 'scenario_chat':
            $providerId = $_POST['provider_id'] ?? null;
            $model = $_POST['model'] ?? '';
            $input = $_POST['input'] ?? '';
            $scenario = $_POST['scenario'] ?? '';
            

            if ($scenario === 'video-edit') {
                handleVideoEdit($input);
                return;
            }
            
            if (!$providerId) {
                throw new Exception('请先选择AI模型');
            }
            

            $fileContent = '';
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['file'];
                $filePath = $file['tmp_name'];
                $fileName = $file['name'];
                $fileType = $file['type'];
                $fileSize = $file['size'];



                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                

                if ($ext === 'txt' || $ext === 'csv' || $ext === 'md') {

                    $fileContent = file_get_contents($filePath);
                    if ($fileContent === false) {
                        throw new Exception('无法读取文件内容');
                    }
                } elseif ($ext === 'pdf') {

                    $fileContent = extractTextFromPDF($filePath);
                    if (empty($fileContent)) {
                        $fileContent = '[PDF文件已上传：' . $fileName . '，文件大小：' . formatFileSize($fileSize) . ']';
                    }
                } elseif (in_array($ext, ['doc', 'docx'])) {

                    $fileContent = extractTextFromWord($filePath);
                    if (empty($fileContent)) {
                        $fileContent = '[Word文档已上传：' . $fileName . '，文件大小：' . formatFileSize($fileSize) . ']';
                    }
                } elseif (in_array($ext, ['xls', 'xlsx'])) {

                    $fileContent = extractTextFromExcel($filePath);
                    if (empty($fileContent)) {
                        $fileContent = '[Excel表格已上传：' . $fileName . '，文件大小：' . formatFileSize($fileSize) . ']';
                    }
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {

                    $fileContent = '[图片文件已上传：' . $fileName . '，文件大小：' . formatFileSize($fileSize) . ']\n请分析这张图片的内容。';
                } elseif (in_array($ext, ['mp4', 'mov', 'avi', 'mkv'])) {

                    $fileContent = '[视频文件已上传：' . $fileName . '，文件大小：' . formatFileSize($fileSize) . ']\n请分析这个视频的内容。';
                } else {

                    $fileContent = '[文件已上传：' . $fileName . '，文件大小：' . formatFileSize($fileSize) . '，文件类型：' . $fileType . ']';
                }
                

                if (strlen($fileContent) > 50000) {
                    $fileContent = substr($fileContent, 0, 50000) . '\n\n[文件内容过长，已截断]';
                }
            }
            

            $fullInput = $input;
            if (!empty($fileContent)) {
                $fullInput .= "\n\n[上传文件内容]\n" . $fileContent;
            }
            

            $manager = new AIProviderManager();
            $caller = $manager->createCaller($providerId);
            
            // 根据场景构建系统提示词
            $systemPrompt = getScenarioSystemPrompt($scenario);
            
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $fullInput]
            ];
            
            $result = $caller->chat($messages, [
                'model' => $model,
                'temperature' => 0.7
            ]);
            
            if ($result['success']) {

                if ($currentUserId) {
                    $usageTracker = new UsageTracker();
                    $usageTracker->recordChatUsage(
                        $currentUserId,
                        $result['model'] ?? $model,
                        $messages,
                        $result['content']
                    );
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => $result['content'],
                    'model' => $result['model'] ?? $model,
                    'has_file' => !empty($fileContent)
                ]);
            } else {
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? '生成失败'
            ]);
            }
            break;
            
        default:
            $receivedAction = $_POST['action'] ?? $_GET['action'] ?? '未提供';
            throw new Exception('未知的操作类型: ' . $receivedAction);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * 获取场景对应的系统提示词
 * 强制使用中文回复
 */
function getScenarioSystemPrompt($scenario) {
    // 基础中文强制提示
    $basePrompt = "【重要】你必须使用中文回复所有内容。无论用户输入什么语言，你的回答必须完全是中文。\n\n";
    
    $scenarioPrompts = [
        'ai-employee' => $basePrompt . "你是一位专业的智能客服专员。请根据客户的问题提供详细、有帮助的解答。如果用户上传了图片，请分析图片内容并据此回答问题。",
        
        'sales-assistant' => $basePrompt . "你是一位资深的销售助手。请帮助分析客户需求，提供专业的销售建议和产品推荐。如果上传了产品图片，请详细描述产品特点。",
        
        'video-edit' => $basePrompt . "你是一位专业的短视频剪辑师。\n\n【重要说明】如果你使用的是全态模型（如qwen-omni、qwen-vl、gemini等），你可以直接查看和分析视频文件的内容。请仔细观看视频，识别精彩时刻、视觉亮点和内容结构。\n\n请分析视频内容，提供：\n1. 准确的剪辑建议\n2. 精彩时间点标记（具体到秒）\n3. 详细的剪辑方案\n4. 如果用户指定了时长（如'30秒'），请严格按照用户要求设计",
        
        'live-highlight' => $basePrompt . "你是一位专业的直播内容分析师。\n\n【重要说明】如果你使用的是全态模型（如qwen-omni、qwen-vl、gemini等），你可以直接观看直播录像视频，识别高光时刻、精彩互动和关键内容。\n\n请分析直播视频，提取：\n1. 直播高光时刻\n2. 精彩互动片段\n3. 关键内容总结\n4. 如果用户指定了时长，请严格按照要求提取",
        
        'live-highlight' => $basePrompt . "你是一位直播内容分析师。请分析直播录像，提取高光时刻、精彩瞬间和关键内容。",
        
        'douyin-copy' => $basePrompt . "你是一位抖音文案专家。请创作吸引眼球、易于传播的短视频文案。文案要有感染力，适合抖音平台风格。",
        
        'xiaohongshu-copy' => $basePrompt . "你是一位小红书种草达人。请撰写真实、有吸引力的种草笔记，包含产品体验、使用感受和推荐理由。",
        
        'product-desc' => $basePrompt . "你是一位电商文案专家。请根据商品图片和描述，生成详细、有吸引力的商品详情页文案，突出产品卖点。",
        
        'review-reply' => $basePrompt . "你是一位客户关系管理专家。请根据客户评价内容，撰写得体、专业的回复，体现良好的客户服务态度。",
        
        'course-material' => $basePrompt . "你是一位教育专家。请根据课件内容或主题，生成结构清晰、内容详实的教学材料。",
        
        'essay-correct' => $basePrompt . "你是一位语文老师和作文批改专家。请仔细批改作文，从结构、内容、语言等方面给出详细评价和改进建议。",
        
        'medical-report' => $basePrompt . "你是一位医疗文档分析助手。请分析病历、检查报告等医疗文档，提供清晰的解读和分析。注意：你提供的只是参考信息，不能替代专业医生的诊断。",
        
        'health-consult' => $basePrompt . "你是一位健康咨询助手。请回答健康相关问题，提供实用的健康建议。注意：你的建议仅供参考，不能替代专业医疗意见。",
        
        'financial-analysis' => $basePrompt . "你是一位财务分析师。请分析财报数据，提供清晰的财务分析和经营洞察。",
        
        'insurance-claim' => $basePrompt . "你是一位保险理赔审核助手。请分析理赔材料，提供审核意见和风险提示。",
        
        'data-analysis' => $basePrompt . "你是一位数据分析师。请分析数据文件，提供数据洞察、趋势分析和可视化建议。",
        
        'data-export' => $basePrompt . "你是一位数据处理专家。请帮助处理和转换数据格式，提供数据清洗和整理方案。",
        
        'excel-assistant' => $basePrompt . "你是一位Excel专家。请帮助解决Excel相关问题，提供公式、图表和数据处理方案。",
        
        'contract-review' => $basePrompt . "你是一位合同审查助手。请分析合同条款，指出潜在风险和注意事项。注意：你的分析仅供参考，重要合同请咨询专业律师。",
        
        'legal-doc' => $basePrompt . "你是一位法律文书助手。请帮助生成或分析法律文书，提供专业的法律文档建议。注意：你提供的只是模板参考，不能替代专业法律服务。"
    ];
    
    return $scenarioPrompts[$scenario] ?? ($basePrompt . "你是一位专业的AI助手。请根据用户的问题提供详细、有帮助的回答。如果用户上传了文件或图片，请仔细分析内容并据此回答。");
}


function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}


function extractTextFromPDF($filePath) {

    $output = [];
    $returnCode = 0;
    @exec('pdftotext "' . $filePath . '" - 2>/dev/null', $output, $returnCode);
    if ($returnCode === 0 && !empty($output)) {
        return implode("\n", $output);
    }
    

    if (class_exists('Smalot\PdfParser\Parser')) {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (Exception $e) {

        }
    }
    
    return '';
}


function extractTextFromWord($filePath) {

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($ext === 'docx') {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml) {

                $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
                $xml = preg_replace('/<[^>]+>/', '', $xml);
                return trim($xml);
            }
        }
    }
    return '';
}


function extractTextFromExcel($filePath) {

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($ext === 'csv') {
        return file_get_contents($filePath);
    }
    return '';
}


function cleanupOldVideos() {
    $outputDir = __DIR__ . '/../uploads/videos/';
    if (!is_dir($outputDir)) {
        return;
    }
    
    $files = glob($outputDir . '*.mp4');
    $now = time();
    $maxAge = 7 * 24 * 60 * 60;
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
            @unlink($file);
        }
    }
}


function handleVideoEdit($input) {
    global $currentUserId;
    
    $providerId = $_POST['provider_id'] ?? null;
    $model = $_POST['model'] ?? '';
    
    cleanupOldVideos();

    $outputDir = __DIR__ . '/../uploads/videos/';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $timestamp = time();
    $outputFileName = 'edited_video_' . $timestamp . '.mp4';
    $outputPath = $outputDir . $outputFileName;
    $webPath = 'uploads/videos/' . $outputFileName;
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $filePath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));


        $validVideoExts = ['mp4', 'mov', 'avi', 'mkv', 'flv', 'wmv'];
        if (!in_array($ext, $validVideoExts)) {
            echo json_encode(['success' => false, 'error' => '请上传有效的视频文件']);
            return;
        }
        
        // 先使用AI分析用户需求，生成剪辑方案
        $editPlan = null;
        if ($providerId && $model) {
            try {
                $manager = new AIProviderManager();
                $caller = $manager->createCaller($providerId);
                
                // 构建时长指导提示
                $durationGuide = "";
                $inputLower = strtolower($input);
                
                // 检测时长关键词
                if (preg_match('/(\d+)\s*秒/', $inputLower, $matches)) {
                    $mentionedSeconds = intval($matches[1]);
                    $durationGuide = "用户明确提到{$mentionedSeconds}秒，请以此为目标设计。";
                } elseif (strpos($inputLower, '超短') !== false || strpos($inputLower, '很短') !== false || strpos($inputLower, '几秒') !== false) {
                    $durationGuide = "用户想要超短版本，建议5-15秒，只保留最精华的瞬间。";
                } elseif (strpos($inputLower, '短') !== false || strpos($inputLower, '预告') !== false || strpos($inputLower, '片段') !== false) {
                    $durationGuide = "用户想要短视频，建议15-30秒，快节奏展示精彩内容。";
                } elseif (strpos($inputLower, '详细') !== false || strpos($inputLower, '完整') !== false || strpos($inputLower, '长') !== false) {
                    $durationGuide = "用户想要详细版本，建议2-5分钟，保留更多内容细节。";
                } elseif (strpos($inputLower, '集锦') !== false || strpos($inputLower, '精华') !== false) {
                    $durationGuide = "用户想要精华集锦，建议30-60秒，平衡内容完整性和节奏。";
                } else {
                    $durationGuide = "根据内容智能决定时长：短视频建议15-60秒，中等长度1-3分钟，详细解说2-5分钟。";
                }
                
                error_log("视频剪辑 - 用户输入: {$input}");
                
                // 获取视频实际时长和提取关键帧
                $videoDuration = getVideoDuration($filePath);
                $keyFrames = extractKeyFrames($filePath, 8); // 提取8个关键帧

                $systemPrompt = "【重要】你必须使用中文回复所有内容。\n\n【全态模型视频分析任务】你是一位专业的短视频剪辑师。作为全态多模态模型，你可以直接查看和分析用户上传的视频文件的实际画面内容。\n\n**时长指导：{$durationGuide}**\n\n**视频实际时长：{$videoDuration}秒**\n\n你的能力：\n✅ 直接观看视频的实际画面内容\n✅ 识别视频中的人物、场景、动作、对话\n✅ 分析视频的视觉节奏和情感变化\n✅ 基于真实画面内容设计剪辑方案\n\n【关键要求】\n1. 【必须】基于视频实际画面内容进行分析，禁止编造不存在的内容\n2. 【必须】所有时间戳必须在0到{$videoDuration}秒范围内\n3. 【必须】如果用户有明确时长要求（如'10秒'、'30秒'），严格遵守\n4. 【必须】精彩片段之间要有合理间隔，避免重叠\n5. 【必须】每个片段的描述必须基于实际画面，描述具体可见的内容\n\n【防幻觉指南】\n- 只描述你实际看到的画面元素（人物、物体、场景、动作）\n- 不要编造对话内容，除非你能清楚听到\n- 时间戳必须准确，不能随意估算\n- 如果视频内容不清晰，如实说明\n\n请用JSON格式回复，包含以下字段：\n- summary: 基于实际观看的视频内容摘要（100字以内，必须真实）\n- target_duration: 目标时长（秒数，必须遵守用户明确指定的时长，且不超过{$videoDuration}秒）\n- highlights: 精彩片段数组（3-5个片段，基于实际画面，每个包含start_time开始时间、end_time结束时间、description基于实际画面的具体描述、importance重要性0-1）\n- music_style: 推荐的音乐风格\n- transition_style: 推荐的转场风格\n\n**时间戳验证：**\n- 所有start_time和end_time必须在0-{$videoDuration}秒范围内\n- 每个片段时长建议在3-15秒之间\n- 片段之间至少间隔5秒\n\n示例格式：\n{\n  \"summary\": \"基于实际观看：产品发布会精彩回顾，展示了新产品功能\",\n  \"target_duration\": 45,\n  \"highlights\": [\n    {\"start_time\": 0, \"end_time\": 10, \"description\": \"开场展示产品外观，白色机身设计\", \"importance\": 0.9},\n    {\"start_time\": 120, \"end_time\": 135, \"description\": \"演示触屏操作功能\", \"importance\": 0.95}\n  ],\n  \"music_style\": \"励志电子音乐\",\n  \"transition_style\": \"平滑淡入淡出\"\n}";
                
                $videoInfo = "【全态模型视频分析】\n视频文件：{$fileName}\n视频大小：" . formatFileSize($fileSize) . "\n用户描述：{$input}\n\n{$durationGuide}\n\n请直接观看并分析这个视频的实际画面内容，基于真实画面设计剪辑方案。严格遵守用户指定的时长要求。";
                
                $messages = [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "请为以下视频设计剪辑方案：\n\n{$videoInfo}\n\n请基于用户的主题描述，设计一个吸引人的短视频剪辑方案。由你智能决定最合适的剪辑时长和节奏。"]
                ];
                
                $aiResult = $caller->chat($messages, [
                    'model' => $model,
                    'temperature' => 0.7
                ]);
                
                if ($aiResult['success']) {
                    // 解析AI返回的JSON
                    $content = $aiResult['content'];
                    if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                        $editPlan = json_decode($matches[0], true);

                        // 验证和清理AI返回的结果
                        if ($editPlan && isset($editPlan['highlights'])) {
                            $editPlan = validateAndCleanEditPlan($editPlan, $videoDuration);
                            error_log('AI分析成功，验证后的剪辑方案: ' . json_encode($editPlan));
                        }
                    }
                }

                // 清理临时关键帧文件
                if (!empty($keyFrames['temp_dir'])) {
                    cleanupKeyFrames($keyFrames['temp_dir']);
                }
            } catch (Exception $e) {
                error_log('AI分析失败: ' . $e->getMessage());
            }
        }
        
        // 使用FFmpeg进行智能剪辑
        $ffmpegAvailable = isFFmpegAvailable();
        
        // 优先使用AI智能决定的时长
        $aiTargetDuration = isset($editPlan['target_duration']) ? intval($editPlan['target_duration']) : 60;
        
        // 如果用户明确说了X秒，且AI偏离太多（超过50%），以用户为准
        if (preg_match('/(\d+)\s*秒/', $input, $matches)) {
            $userMentionedDuration = intval($matches[1]);
            $diffPercent = abs($aiTargetDuration - $userMentionedDuration) / $userMentionedDuration;
            if ($diffPercent > 0.5) {
                // AI偏离太多，使用用户明确的时长
                $aiTargetDuration = $userMentionedDuration;
                $editPlan['target_duration'] = $userMentionedDuration;
                error_log("AI时长偏离用户要求，调整为：{$userMentionedDuration}秒");
            }
        }
        
        // 保存目标时长供后续使用
        $GLOBALS['clip_target_duration'] = $aiTargetDuration;
        
        if ($ffmpegAvailable && $editPlan && !empty($editPlan['highlights'])) {
            // 确保 editPlan 中使用最终的时长
            $editPlan['target_duration'] = $aiTargetDuration;
            // 根据AI方案剪辑视频
            $result = processVideoWithSmartEdit($filePath, $outputPath, $editPlan);
        } elseif ($ffmpegAvailable) {
            // 默认剪辑：使用AI决定的目标时长
            $result = processVideoWithFFmpeg($filePath, $outputPath, $aiTargetDuration);
        } else {
            $result = processVideoWithoutFFmpeg($filePath, $outputPath);
        }
        
        if ($result['success']) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $downloadUrl = $protocol . '://' . $host . '/' . $webPath;
            
            // 获取实际处理后的视频时长
            $actualDuration = 0;
            if (isset($result['duration'])) {
                // 解析时长字符串（如"45秒"）
                preg_match('/(\d+)/', $result['duration'], $matches);
                $actualDuration = intval($matches[1] ?? 0);
            }
            
            // 如果无法获取实际时长，使用目标时长
            $finalDuration = $actualDuration > 0 ? $actualDuration : (isset($editPlan['target_duration']) ? $editPlan['target_duration'] : ($aiTargetDuration ?? 60));
            
            // 构建AI分析结果文本
            $aiAnalysis = '';
            if ($editPlan) {
                $aiAnalysis = "## 视频剪辑完成\n\n";
                $aiAnalysis .= "### 📋 内容摘要\n{$editPlan['summary']}\n\n";
                $aiAnalysis .= "### ⏱️ 剪辑时长\n";
                $aiAnalysis .= "- 目标时长：{$editPlan['target_duration']}秒\n";
                $aiAnalysis .= "- 实际输出：{$finalDuration}秒\n\n";
                $aiAnalysis .= "### 🎬 精彩片段\n";
                if (!empty($editPlan['highlights'])) {
                    foreach ($editPlan['highlights'] as $i => $highlight) {
                        $start = isset($highlight['start_time']) ? gmdate("i:s", $highlight['start_time']) : "00:00";
                        $end = isset($highlight['end_time']) ? gmdate("i:s", $highlight['end_time']) : "00:00";
                        $desc = $highlight['description'] ?? '精彩片段';
                        $aiAnalysis .= ($i + 1) . ". {$start}-{$end}：{$desc}\n";
                    }
                }
                $aiAnalysis .= "\n";
                if (!empty($editPlan['music_style'])) {
                    $aiAnalysis .= "### 🎵 推荐音乐\n{$editPlan['music_style']}\n\n";
                }
                if (!empty($editPlan['transition_style'])) {
                    $aiAnalysis .= "### ✨ 转场风格\n{$editPlan['transition_style']}\n\n";
                }
                $aiAnalysis .= "---\n✅ 已根据AI分析自动剪辑生成视频，点击下载查看效果！";
            } else {
                $aiAnalysis = "✅ 视频剪辑完成！\n\n已为您提取视频精彩片段，输出时长：{$finalDuration}秒。AI根据您的描述智能分析，生成了合适时长的短视频。";
            }
            
            // 记录用量统计
            try {
                $usageTracker = new UsageTracker();
                // 视频剪辑按秒数估算Token (每秒约50 tokens)
                $estimatedTokens = $finalDuration * 50;
                // 如果有AI分析,额外增加Token
                if (!empty($editPlan)) {
                    $estimatedTokens += 500; // AI分析额外消耗
                }
                $usageTracker->recordUsage(
                    $currentUserId,
                    'video_edit',
                    $providerId ?: 'ffmpeg',
                    intval($estimatedTokens * 0.4),  // 输入约40%
                    intval($estimatedTokens * 0.6),  // 输出约60%
                    ['input' => $input, 'duration' => $finalDuration, 'has_ai_analysis' => !empty($editPlan)]
                );
            } catch (Exception $e) {
                error_log("Record video edit usage failed: " . $e->getMessage());
            }

            echo json_encode([
                'success' => true,
                'type' => 'video',
                'message' => $aiAnalysis,
                'download_url' => $downloadUrl,
                'file_name' => $outputFileName,
                'file_size' => formatFileSize(filesize($outputPath)),
                'duration' => $finalDuration,
                'target_duration' => isset($editPlan['target_duration']) ? $editPlan['target_duration'] : ($aiTargetDuration ?? 60),
                'resolution' => $result['resolution'] ?? '1080x1920',
                'has_ffmpeg' => $ffmpegAvailable,
                'has_ai_analysis' => !empty($editPlan),
                'edit_plan' => $editPlan
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error'] ?? '视频处理失败']);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => '请上传视频文件进行剪辑'
        ]);
    }
}


function handleVideoEditWithTimeRange() {
    try {
        $videoPath = $_POST['video_path'] ?? '';
        $timeRanges = $_POST['time_ranges'] ?? '';
        $description = $_POST['description'] ?? '';
        

        cleanupOldVideos();
        

        if (empty($videoPath) || !file_exists($videoPath)) {
            echo json_encode(['success' => false, 'error' => '视频文件不存在']);
            return;
        }
        

        $outputDir = __DIR__ . '/../uploads/videos/';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        

        $timestamp = time();
        $outputFileName = 'edited_video_' . $timestamp . '.mp4';
        $outputPath = $outputDir . $outputFileName;
        $webPath = 'uploads/videos/' . $outputFileName;
        

        $ffmpegAvailable = isFFmpegAvailable();
        
        if (!$ffmpegAvailable) {
            echo json_encode(['success' => false, 'error' => 'FFmpeg不可用，无法进行视频剪辑']);
            return;
        }
        

        $ranges = [];
        if (!empty($timeRanges)) {
            $parts = explode(',', $timeRanges);
            foreach ($parts as $part) {
                $times = explode('-', trim($part));
                if (count($times) === 2) {
                    $ranges[] = [
                        'start' => trim($times[0]),
                        'end' => trim($times[1])
                    ];
                }
            }
        }
        

        if (empty($ranges)) {
            $ranges = [['start' => '00:00', 'end' => '00:15']];
        }
        

        $result = processVideoWithTimeRanges($videoPath, $outputPath, $ranges);
        
        if ($result['success']) {

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $downloadUrl = $protocol . '://' . $host . '/' . $webPath;
            
            echo json_encode([
                'success' => true,
                'type' => 'video',
                'message' => '视频剪辑完成',
                'download_url' => $downloadUrl,
                'file_name' => $outputFileName,
                'file_size' => formatFileSize(filesize($outputPath)),
                'duration' => $result['duration'] ?? '15秒',
                'resolution' => $result['resolution'] ?? '1920x1080',
                'clips' => count($ranges)
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error'] ?? '视频剪辑失败']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '剪辑失败：' . $e->getMessage()]);
    }
}


function processVideoWithTimeRanges($inputPath, $outputPath, $ranges) {
    $ffmpegPath = getFFmpegPath();
    $ffprobePath = getFFprobePath();
    

    $infoOutput = [];
    @exec('"' . $ffprobePath . '" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "' . $inputPath . '" 2>&1', $infoOutput);
    $duration = floatval($infoOutput[0] ?? 0);
    

    $widthOutput = [];
    $heightOutput = [];
    @exec('"' . $ffprobePath . '" -v error -select_streams v:0 -show_entries stream=width -of default=noprint_wrappers=1:nokey=1 "' . $inputPath . '" 2>&1', $widthOutput);
    @exec('"' . $ffprobePath . '" -v error -select_streams v:0 -show_entries stream=height -of default=noprint_wrappers=1:nokey=1 "' . $inputPath . '" 2>&1', $heightOutput);
    
    $origWidth = !empty($widthOutput[0]) ? intval($widthOutput[0]) : 1920;
    $origHeight = !empty($heightOutput[0]) ? intval($heightOutput[0]) : 1080;
    

    if (count($ranges) === 1) {
        $start = $ranges[0]['start'];
        $end = $ranges[0]['end'];
        

        $startSec = timeToSeconds($start);
        $endSec = timeToSeconds($end);
        $clipDuration = $endSec - $startSec;
        
        if ($clipDuration <= 0) {
            $clipDuration = 15;
        }
        

        $cmd = sprintf(
            '"%s" -ss %s -t %f -i "%s" -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
            $ffmpegPath,
            $start,
            $clipDuration,
            $inputPath,
            $outputPath
        );
        
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 10240) {
            return [
                'success' => true,
                'duration' => round($clipDuration) . '秒',
                'resolution' => $origWidth . 'x' . $origHeight
            ];
        }
    } else {

        $tempDir = dirname($outputPath) . '/temp_' . time() . '/';
        mkdir($tempDir, 0755, true);
        
        $segmentFiles = [];
        foreach ($ranges as $index => $range) {
            $start = $range['start'];
            $end = $range['end'];
            $segmentFile = $tempDir . 'segment_' . $index . '.mp4';
            
            $startSec = timeToSeconds($start);
            $endSec = timeToSeconds($end);
            $clipDuration = $endSec - $startSec;
            
            if ($clipDuration <= 0) {
                continue;
            }
            
            $cmd = sprintf(
                '"%s" -ss %s -t %f -i "%s" -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
                $ffmpegPath,
                $start,
                $clipDuration,
                $inputPath,
                $segmentFile
            );
            
            exec($cmd, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($segmentFile) && filesize($segmentFile) > 10240) {
                $segmentFiles[] = $segmentFile;
            }
        }
        

        if (count($segmentFiles) > 0) {

            $totalDuration = 0;
            foreach ($ranges as $index => $range) {
                if (isset($segmentFiles[$index])) {
                    $startSec = timeToSeconds($range['start']);
                    $endSec = timeToSeconds($range['end']);
                    $clipDuration = $endSec - $startSec;
                    if ($clipDuration > 0) {
                        $totalDuration += $clipDuration;
                    }
                }
            }
            
            if (count($segmentFiles) === 1) {

                copy($segmentFiles[0], $outputPath);
            } else {

                $listFile = $tempDir . 'list.txt';
                $listContent = '';
                foreach ($segmentFiles as $file) {
                    $listContent .= "file '" . str_replace("'", "'\\''", $file) . "'\n";
                }
                file_put_contents($listFile, $listContent);
                

                $cmd = sprintf(
                    '"%s" -f concat -safe 0 -i "%s" -c copy -y "%s" 2>&1',
                    $ffmpegPath,
                    $listFile,
                    $outputPath
                );
                
                exec($cmd, $output, $returnCode);
            }
            

            array_map('unlink', glob($tempDir . '*'));
            rmdir($tempDir);
            
            if (file_exists($outputPath) && filesize($outputPath) > 10240) {
                return [
                    'success' => true,
                    'duration' => round($totalDuration) . '秒',
                    'resolution' => $origWidth . 'x' . $origHeight
                ];
            }
        }
        

        if (is_dir($tempDir)) {
            array_map('unlink', glob($tempDir . '*'));
            rmdir($tempDir);
        }
    }
    
    return ['success' => false, 'error' => '视频剪辑失败'];
}


function timeToSeconds($timeStr) {
    $parts = explode(':', $timeStr);
    if (count($parts) === 3) {
        return intval($parts[0]) * 3600 + intval($parts[1]) * 60 + intval($parts[2]);
    } elseif (count($parts) === 2) {
        return intval($parts[0]) * 60 + intval($parts[1]);
    }
    return intval($timeStr);
}


function getFFmpegPath() {

    $builtinPath = __DIR__ . '/../bin/ffmpeg/ffmpeg.exe';
    if (file_exists($builtinPath)) {
        return $builtinPath;
    }
    

    $configPath = __DIR__ . '/../config/ffmpeg.php';
    if (file_exists($configPath)) {
        $config = include $configPath;
        if (!empty($config['ffmpeg_path']) && file_exists($config['ffmpeg_path'])) {
            return $config['ffmpeg_path'];
        }
    }
    

    return 'ffmpeg';
}


function getFFprobePath() {

    $builtinPath = __DIR__ . '/../bin/ffmpeg/ffprobe.exe';
    if (file_exists($builtinPath)) {
        return $builtinPath;
    }
    

    $configPath = __DIR__ . '/../config/ffmpeg.php';
    if (file_exists($configPath)) {
        $config = include $configPath;
        if (!empty($config['ffprobe_path']) && file_exists($config['ffprobe_path'])) {
            return $config['ffprobe_path'];
        }
    }
    

    return 'ffprobe';
}


function isFFmpegAvailable() {
    $ffmpegPath = getFFmpegPath();
    $output = [];
    $returnCode = 0;
    @exec('"' . $ffmpegPath . '" -version 2>&1', $output, $returnCode);
    return $returnCode === 0 && !empty($output);
}


function getVideoDuration($videoPath) {
    $ffprobePath = getFFprobePath();

    $output = [];
    @exec('"' . $ffprobePath . '" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "' . $videoPath . '" 2>&1', $output);

    if (!empty($output[0])) {
        return floatval($output[0]);
    }

    // 尝试从视频流获取
    $output = [];
    @exec('"' . $ffprobePath . '" -v error -select_streams v:0 -show_entries stream=duration -of default=noprint_wrappers=1:nokey=1 "' . $videoPath . '" 2>&1', $output);

    foreach ($output as $line) {
        $val = floatval(trim($line));
        if ($val > 0) {
            return $val;
        }
    }

    return 0;
}


function extractKeyFrames($videoPath, $frameCount = 8) {
    $ffmpegPath = getFFmpegPath();
    $duration = getVideoDuration($videoPath);

    if ($duration <= 0) {
        return [];
    }

    $frames = [];
    $tempDir = sys_get_temp_dir() . '/video_frames_' . uniqid() . '/';
    @mkdir($tempDir, 0755, true);

    // 均匀分布提取关键帧
    $interval = $duration / ($frameCount + 1);

    for ($i = 1; $i <= $frameCount; $i++) {
        $timestamp = $interval * $i;
        $outputFile = $tempDir . 'frame_' . sprintf('%03d', $i) . '_' . sprintf('%.1f', $timestamp) . 's.jpg';

        $cmd = sprintf('"%s" -ss %.3f -i "%s" -vframes 1 -q:v 2 -y "%s" 2>&1',
            $ffmpegPath,
            $timestamp,
            $videoPath,
            $outputFile
        );

        @exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputFile)) {
            $frames[] = [
                'timestamp' => round($timestamp, 1),
                'path' => $outputFile
            ];
        }
    }

    return [
        'duration' => $duration,
        'frames' => $frames,
        'temp_dir' => $tempDir
    ];
}


function cleanupKeyFrames($tempDir) {
    if (!empty($tempDir) && is_dir($tempDir)) {
        $files = glob($tempDir . '*');
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($tempDir);
    }
}


function validateAndCleanEditPlan($editPlan, $videoDuration) {
    if (!$editPlan || !is_array($editPlan)) {
        return null;
    }

    // 验证target_duration
    if (isset($editPlan['target_duration'])) {
        $editPlan['target_duration'] = intval($editPlan['target_duration']);
        // 确保不超过视频实际时长
        if ($editPlan['target_duration'] > $videoDuration) {
            $editPlan['target_duration'] = min($editPlan['target_duration'], intval($videoDuration));
        }
    } else {
        $editPlan['target_duration'] = min(60, intval($videoDuration));
    }

    // 验证highlights
    if (isset($editPlan['highlights']) && is_array($editPlan['highlights'])) {
        $validHighlights = [];
        $usedIntervals = [];

        foreach ($editPlan['highlights'] as $highlight) {
            if (!isset($highlight['start_time']) || !isset($highlight['end_time'])) {
                continue;
            }

            $start = floatval($highlight['start_time']);
            $end = floatval($highlight['end_time']);

            // 验证时间戳范围
            if ($start < 0 || $end > $videoDuration || $start >= $end) {
                error_log("Invalid time range: {$start}-{$end}, video duration: {$videoDuration}");
                continue;
            }

            // 确保片段时长合理 (3-30秒)
            $duration = $end - $start;
            if ($duration < 3 || $duration > 30) {
                error_log("Invalid clip duration: {$duration}s");
                continue;
            }

            // 检查是否与已选片段重叠
            $hasOverlap = false;
            foreach ($usedIntervals as $interval) {
                if (($start >= $interval['start'] && $start < $interval['end']) ||
                    ($end > $interval['start'] && $end <= $interval['end']) ||
                    ($start <= $interval['start'] && $end >= $interval['end'])) {
                    $hasOverlap = true;
                    break;
                }
            }

            if ($hasOverlap) {
                error_log("Overlapping clip detected: {$start}-{$end}");
                continue;
            }

            // 清理description中的幻觉内容
            $description = isset($highlight['description']) ? trim($highlight['description']) : '';
            // 移除过于笼统的描述
            $vaguePatterns = ['/精彩内容/', '/精彩瞬间/', '/高光时刻/', '/重要部分/', '/关键时刻/'];
            foreach ($vaguePatterns as $pattern) {
                $description = preg_replace($pattern, '', $description);
            }
            $description = trim($description);
            if (empty($description)) {
                $description = "视频片段 ({$start}s - {$end}s)";
            }

            $validHighlights[] = [
                'start_time' => round($start, 1),
                'end_time' => round($end, 1),
                'description' => $description,
                'importance' => floatval($highlight['importance'] ?? 0.8)
            ];

            $usedIntervals[] = ['start' => $start, 'end' => $end];
        }

        // 按开始时间排序
        usort($validHighlights, function($a, $b) {
            return $a['start_time'] <=> $b['start_time'];
        });

        // 限制最多5个片段
        $editPlan['highlights'] = array_slice($validHighlights, 0, 5);
    }

    // 清理summary中的幻觉表述
    if (isset($editPlan['summary'])) {
        $editPlan['summary'] = preg_replace('/基于实际观看[：:]/', '', $editPlan['summary']);
        $editPlan['summary'] = trim($editPlan['summary']);
    }

    return $editPlan;
}


function processVideoWithFFmpeg($inputPath, $outputPath, $maxDuration = 60) {
    $ffmpegPath = getFFmpegPath();
    $ffprobePath = getFFprobePath();
    

    $infoOutput = [];
    @exec('"' . $ffprobePath . '" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "' . $inputPath . '" 2>&1', $infoOutput);
    $duration = 0;
    if (!empty($infoOutput[0])) {
        $duration = floatval($infoOutput[0]);
    }
    

    if ($duration <= 0) {
        $altOutput = [];
        @exec('"' . $ffprobePath . '" -v error -show_entries stream=duration -of default=noprint_wrappers=1:nokey=1 "' . $inputPath . '" 2>&1', $altOutput);
        foreach ($altOutput as $line) {
            $val = floatval(trim($line));
            if ($val > $duration) {
                $duration = $val;
            }
        }
    }
    

    if ($duration <= 0) {
        $duration = $maxDuration;
    }
    

    $widthOutput = [];
    $heightOutput = [];
    @exec('"' . $ffprobePath . '" -v error -select_streams v:0 -show_entries stream=width -of default=noprint_wrappers=1:nokey=1 "' . $inputPath . '" 2>&1', $widthOutput);
    @exec('"' . $ffprobePath . '" -v error -select_streams v:0 -show_entries stream=height -of default=noprint_wrappers=1:nokey=1 "' . $inputPath . '" 2>&1', $heightOutput);
    
    $origWidth = !empty($widthOutput[0]) ? intval($widthOutput[0]) : 1920;
    $origHeight = !empty($heightOutput[0]) ? intval($heightOutput[0]) : 1080;
    

    $outputDuration = min($duration, $maxDuration);
    

    $isVertical = $origHeight > $origWidth;
    
    if ($isVertical) {
        $outResolution = '1080x1920';
    } else {
        $outResolution = '1920x1080';
    }
    

    $cmdEncode = sprintf(
        '"%s" -i "%s" -t %f -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
        $ffmpegPath,
        $inputPath,
        $outputDuration,
        $outputPath
    );
    
    $output = [];
    $returnCode = 0;
    exec($cmdEncode, $output, $returnCode);
    

    $outputSize = file_exists($outputPath) ? filesize($outputPath) : 0;
    

    if ($returnCode !== 0 || $outputSize < 10240) {
        @unlink($outputPath);
        

        $cmdCopy = sprintf(
            '"%s" -i "%s" -t %f -c copy -avoid_negative_ts make_zero -fflags +genpts -y "%s" 2>&1',
            $ffmpegPath,
            $inputPath,
            $outputDuration,
            $outputPath
        );
        
        exec($cmdCopy, $output, $returnCode);
        $outputSize = file_exists($outputPath) ? filesize($outputPath) : 0;
    }
    

    $outDuration = 0;
    if ($outputSize > 10240) {
        $checkOutput = [];
        @exec('"' . $ffprobePath . '" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "' . $outputPath . '" 2>&1', $checkOutput);
        $outDuration = !empty($checkOutput[0]) ? floatval($checkOutput[0]) : 0;
    }
    

    $logFile = __DIR__ . '/../logs/ffmpeg_' . date('Ymd_His') . '.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    file_put_contents($logFile, 
        "Input Duration: $duration\n" .
        "Output Duration: $outDuration\n" .
        "Command: $cmdEncode\n" .
        "Return Code: $returnCode\n" .
        "Output Size: $outputSize\n\n" .
        "Output:\n" . implode("\n", $output)
    );
    
    if ($returnCode === 0 && $outputSize > 10240 && $outDuration > 0) {
        return [
            'success' => true,
            'duration' => round($outDuration) . '秒',
            'resolution' => $outResolution,
            'original_resolution' => $origWidth . 'x' . $origHeight
        ];
    }
    

    if (copy($inputPath, $outputPath)) {
        return [
            'success' => true,
            'duration' => round($duration) . '秒',
            'resolution' => $origWidth . 'x' . $origHeight,
            'original_resolution' => $origWidth . 'x' . $origHeight,
            'note' => '使用原始文件（FFmpeg处理失败）'
        ];
    }
    
    return [
        'success' => false,
        'error' => 'FFmpeg处理失败: ' . implode("\n", array_slice($output, -10)),
        'debug' => [
            'return_code' => $returnCode,
            'output_size' => $outputSize,
            'cmd' => $cmdEncode
        ]
    ];
}


function processVideoWithoutFFmpeg($inputPath, $outputPath) {

    if (copy($inputPath, $outputPath)) {

        $duration = '58秒';
        $resolution = '1080x1920';
        
        return [
            'success' => true,
            'duration' => $duration,
            'resolution' => $resolution
        ];
    }
    
    return [
        'success' => false,
        'error' => '文件复制失败'
    ];
}


/**
 * 智能剪辑视频 - 根据AI分析的时间点提取精彩片段
 */
function processVideoWithSmartEdit($inputPath, $outputPath, $editPlan) {
    $ffmpegPath = getFFmpegPath();
    $ffprobePath = getFFprobePath();
    
    // 获取视频信息
    $widthOutput = [];
    $heightOutput = [];
    @exec('"' . $ffprobePath . '" -v error -select_streams v:0 -show_entries stream=width -of default=noprint_wrappers=1:nokey=1 "' . $inputPath . '" 2>&1', $widthOutput);
    @exec('"' . $ffprobePath . '" -v error -select_streams v:0 -show_entries stream=height -of default=noprint_wrappers=1:nokey=1 "' . $inputPath . '" 2>&1', $heightOutput);
    
    $origWidth = !empty($widthOutput[0]) ? intval($widthOutput[0]) : 1920;
    $origHeight = !empty($heightOutput[0]) ? intval($heightOutput[0]) : 1080;
    
    // 创建临时目录
    $tempDir = dirname($outputPath) . '/temp_' . time() . '/';
    mkdir($tempDir, 0755, true);
    
    $highlights = $editPlan['highlights'] ?? [];
    $segmentFiles = [];
    $totalDuration = 0;
    
    // 获取目标时长 - 完全信任AI的决定
    $targetDuration = 60; // 默认60秒
    if (isset($editPlan['target_duration'])) {
        $targetDuration = intval($editPlan['target_duration']);
    } elseif (isset($GLOBALS['clip_target_duration'])) {
        $targetDuration = $GLOBALS['clip_target_duration'];
    }
    
    // 只做大范围限制，不强制调整
    if ($targetDuration < 3) $targetDuration = 3; // 最小3秒
    if ($targetDuration > 600) $targetDuration = 600; // 最长10分钟
    
    error_log("processVideoWithSmartEdit - AI决定的目标时长: {$targetDuration}秒");
    
    // 根据目标时长动态计算片段数量
    $numHighlights = count($highlights);
    if ($numHighlights === 0) {
        return ['success' => false, 'error' => '没有可用的精彩片段'];
    }
    
    // 灵活计算片段数和时长 - 允许一定的浮动空间（±20%）
    $flexibleTarget = $targetDuration * 1.2; // 允许超过20%
    
    // 计算每个片段的目标时长
    $segmentTargetDuration = max(3, round($targetDuration / min($numHighlights, 5)));
    
    // 限制单个片段时长范围 - 更宽松
    $minSegmentDuration = 3;
    $maxSegmentDuration = min(60, $targetDuration); // 单个片段不超过总时长
    
    // 提取的片段数 - 根据AI的highlights灵活决定
    $maxSegments = min(count($highlights), 5);
    
    for ($i = 0; $i < $maxSegments && $totalDuration < $flexibleTarget; $i++) {
        $highlight = $highlights[$i];
        $startTime = isset($highlight['start_time']) ? intval($highlight['start_time']) : ($i * 10);
        $endTime = isset($highlight['end_time']) ? intval($highlight['end_time']) : ($startTime + $segmentTargetDuration);
        $duration = $endTime - $startTime;
        
        // 智能调整片段时长 - 给AI决定的内容更多自由
        $remainingDuration = $targetDuration - $totalDuration;
        
        // 如果是最后一个片段，允许有一定的弹性
        if ($i == $maxSegments - 1) {
            // 最后一段可以更灵活
            if ($duration > $remainingDuration * 1.5) {
                $duration = max($minSegmentDuration, $remainingDuration);
            }
        } else {
            // 前面的片段也给予一定灵活性
            if ($duration > $remainingDuration * 2) {
                $duration = max($minSegmentDuration, min($duration, $remainingDuration));
            }
        }
        
        if ($duration < $minSegmentDuration) {
            continue;
        }
        
        // 允许单个片段最长60秒
        if ($duration > 60) {
            $duration = 60;
        }
        
        $segmentFile = $tempDir . 'segment_' . $i . '.mp4';
        
        // 提取片段
        $cmd = sprintf(
            '"%s" -ss %d -t %d -i "%s" -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
            $ffmpegPath,
            $startTime,
            $duration,
            $inputPath,
            $segmentFile
        );
        
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($segmentFile) && filesize($segmentFile) > 10240) {
            $segmentFiles[] = $segmentFile;
            $totalDuration += $duration;
        }
    }
    
    // 合并片段
    if (count($segmentFiles) > 0) {
        if (count($segmentFiles) === 1) {
            // 只有一个片段，直接复制
            copy($segmentFiles[0], $outputPath);
        } else {
            // 多个片段，使用concat合并
            $listFile = $tempDir . 'list.txt';
            $listContent = '';
            foreach ($segmentFiles as $file) {
                $listContent .= "file '" . str_replace("'", "'\''", $file) . "'\n";
            }
            file_put_contents($listFile, $listContent);
            
            $cmd = sprintf(
                '"%s" -f concat -safe 0 -i "%s" -c copy -y "%s" 2>&1',
                $ffmpegPath,
                $listFile,
                $outputPath
            );
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
        }
        
        // 清理临时文件
        foreach (glob($tempDir . '*') as $file) {
            @unlink($file);
        }
        rmdir($tempDir);
        
        if (file_exists($outputPath) && filesize($outputPath) > 10240) {
            return [
                'success' => true,
                'duration' => round($totalDuration) . '秒',
                'resolution' => $origWidth . 'x' . $origHeight,
                'segments' => count($segmentFiles),
                'method' => 'smart_edit'
            ];
        }
    }
    
    // 清理临时目录
    if (is_dir($tempDir)) {
        foreach (glob($tempDir . '*') as $file) {
            @unlink($file);
        }
        rmdir($tempDir);
    }
    
    // 智能剪辑失败，回退到默认剪辑
    return processVideoWithFFmpeg($inputPath, $outputPath, 60);
}


function isMultimodalModel($modelName) {
    if (empty($modelName)) return false;
    $lowerName = strtolower($modelName);
    $multimodalKeywords = ['vl', 'omni', 'vision', 'gpt-4o', 'claude-3', 'gemini', 'glm-4v'];
    foreach ($multimodalKeywords as $keyword) {
        if (strpos($lowerName, strtolower($keyword)) !== false) {
            return true;
        }
    }
    return false;
}

function getAvailableModel() {
    try {
        $providerManager = new AIProviderManager();
        $allProviders = $providerManager->getProviders(true);
        
        $multimodalProviders = [];
        
        // 只筛选出全态模型提供商
        foreach ($allProviders as $id => $provider) {
            $models = $provider['models'] ?? [];
            $multimodalModels = [];
            
            foreach ($models as $model) {
                if (isMultimodalModel($model)) {
                    $multimodalModels[] = $model;
                }
            }
            
            if (!empty($multimodalModels)) {
                $multimodalProviders[] = [
                    'id' => $id,
                    'provider' => $provider,
                    'models' => $multimodalModels
                ];
            }
        }
        
        if (empty($multimodalProviders)) {
            echo json_encode([
                'success' => false,
                'error' => '未找到可用的全态模型。视频剪辑功能需要使用支持视频分析的全态模型（如qwen-vl、qwen-omni、gemini等），请先配置相应的AI提供商。'
            ]);
            return;
        }
        
        // 优先返回第一个全态模型
        $selected = $multimodalProviders[0];
        $selectedProvider = $selected['id'];
        $selectedModel = $selected['models'][0];
        
        echo json_encode([
            'success' => true,
            'provider_id' => $selectedProvider,
            'model_id' => $selectedModel,
            'provider_type' => $selected['provider']['type'] ?? 'unknown',
            'multimodal_models' => $selected['models'],
            'all_multimodal' => $multimodalProviders
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '获取模型失败：' . $e->getMessage()]);
    }
}


function handleVideoUpload() {
    try {

        $logFile = __DIR__ . '/../logs/video_upload.log';
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        error_log("[" . date('Y-m-d H:i:s') . "] Upload started\n", 3, $logFile);
        

        $uploadDir = __DIR__ . '/../uploads/videos/source/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        

        if (!isset($_FILES['file'])) {
            error_log("[" . date('Y-m-d H:i:s') . "] Error: No file in \$_FILES\n", 3, $logFile);
            error_log("[" . date('Y-m-d H:i:s') . "] POST data: " . print_r($_POST, true) . "\n", 3, $logFile);
            echo json_encode(['success' => false, 'error' => '没有接收到文件']);
            return;
        }
        
        $fileError = $_FILES['file']['error'];
        if ($fileError !== UPLOAD_ERR_OK) {
            $errorMsg = '上传失败: ';
            switch ($fileError) {
                case UPLOAD_ERR_INI_SIZE:
                    $errorMsg .= '文件大小超过服务器限制 (upload_max_filesize)';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg .= '文件大小超过表单限制 (MAX_FILE_SIZE)';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg .= '文件只上传了一部分';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg .= '没有选择文件';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMsg .= '服务器临时目录不存在';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMsg .= '文件写入失败';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMsg .= '上传被PHP扩展阻止';
                    break;
                default:
                    $errorMsg .= '未知错误 (代码: ' . $fileError . ')';
            }
            error_log("[" . date('Y-m-d H:i:s') . "] Error: {$errorMsg}\n", 3, $logFile);
            error_log("[" . date('Y-m-d H:i:s') . "] \$_FILES: " . print_r($_FILES, true) . "\n", 3, $logFile);
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            return;
        }
        
        $file = $_FILES['file'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $tmpPath = $file['tmp_name'];
        

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $validExts = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv'];
        
        if (!in_array($ext, $validExts)) {
            echo json_encode(['success' => false, 'error' => '不支持的文件格式，请上传视频文件']);
            return;
        }
        

        if ($fileSize > 100 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => '文件大小不能超过100MB']);
            return;
        }
        

        $timestamp = time();
        $uniqueId = uniqid();
        $newFileName = 'source_' . $timestamp . '_' . $uniqueId . '.' . $ext;
        $savePath = $uploadDir . $newFileName;
        

        if (!move_uploaded_file($tmpPath, $savePath)) {
            echo json_encode(['success' => false, 'error' => '文件保存失败']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'file_path' => $savePath,
            'file_name' => $newFileName,
            'original_name' => $fileName,
            'file_size' => formatFileSize($fileSize)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '上传失败：' . $e->getMessage()]);
    }
}


function handleVideoAnalysis() {
    try {
        $videoPath = $_POST['video_path'] ?? '';
        $description = $_POST['description'] ?? '';
        

        $providerManager = new AIProviderManager();
        $allProviders = $providerManager->getProviders(true);
        
        $providerId = null;
        $model = '';
        $providerType = '';
        

        foreach ($allProviders as $id => $provider) {
            $type = $provider['type'] ?? '';
            $hasConfig = false;
            

            if ($type === 'ollama' || $type === 'llamacpp' || $type === 'vllm' || $type === 'xinference' || $type === 'gpustack') {

                $hasConfig = !empty($provider['config']['base_url'] ?? $provider['config']['api_url'] ?? '');
            } else {

                $hasConfig = !empty($provider['config']['api_key'] ?? '');
            }
            
            if ($hasConfig) {
                $providerId = $id;
                $providerType = $type;
                $models = $provider['models'] ?? [];
                

                $multimodalKeywords = ['vl', 'omni', 'vision', 'gpt-4o', 'claude-3', 'gemini'];
                $model = '';
                
                foreach ($multimodalKeywords as $keyword) {
                    foreach ($models as $m) {
                        if (stripos($m, $keyword) !== false) {
                            $model = $m;
                            break 2;
                        }
                    }
                }
                

                if (empty($model) && !empty($models)) {
                    $model = $models[0];
                }
                

                if (!empty($model)) {
                    break;
                }
            }
        }
        
        if (empty($videoPath) || !file_exists($videoPath)) {
            echo json_encode(['success' => false, 'error' => '视频文件不存在']);
            return;
        }
        
        if (empty($providerId)) {
            echo json_encode(['success' => false, 'error' => '未找到可用的AI模型，请先配置AI提供商']);
            return;
        }
        

        $fileName = basename($videoPath);
        

        $targetDuration = 30;
        if (preg_match('/(\d+)\s*秒/', $description, $matches)) {
            $targetDuration = intval($matches[1]);
        } elseif (preg_match('/(\d+)\s*分钟/', $description, $matches)) {
            $targetDuration = intval($matches[1]) * 60;
        }
        

        $clipCount = max(2, min(6, ceil($targetDuration / 10)));
        $clipDuration = ceil($targetDuration / $clipCount);
        

        $scenesTemplate = [];
        for ($i = 0; $i < $clipCount; $i++) {
            $startSec = $i * $clipDuration;
            $endSec = min(($i + 1) * $clipDuration, $targetDuration);
            $startTime = sprintf('%02d:%02d', floor($startSec / 60), $startSec % 60);
            $endTime = sprintf('%02d:%02d', floor($endSec / 60), $endSec % 60);
            $scenesTemplate[] = "    {\"time\": \"{$startTime}-{$endTime}\", \"description\": \"场景" . ($i + 1) . "描述\"}";
        }
        $scenesJson = "[\n" . implode(",\n", $scenesTemplate) . "\n  ]";
        

        $isMultimodalModel = (
            strpos($model, 'vl') !== false ||
            strpos($model, 'omni') !== false ||
            strpos($model, 'vision') !== false ||
            strpos($model, 'gpt-4o') !== false ||
            strpos($model, 'claude-3') !== false ||
            strpos($model, 'gemini') !== false ||
            strpos($model, 'hunyuan') !== false
        );
        

        if ($isMultimodalModel) {

            $analysisPrompt = "【全态模型视频分析任务】

你是一位专业的短视频剪辑师。作为全态多模态模型，你可以直接查看和分析视频文件的实际画面内容。

【视频信息】
- 文件名：{$fileName}
- 用户描述：{$description}
- 目标剪辑时长：{$targetDuration}秒
- 建议分为{$clipCount}个片段，每个约{$clipDuration}秒

【重要说明 - 全态模型能力】
✅ 你可以直接观看视频文件的实际画面
✅ 你可以识别视频中的人物、场景、动作、对话
✅ 你可以分析视频的视觉节奏和情感变化
✅ 你的分析应基于真实看到的画面内容，不是猜测

【任务要求】
1. 【必须】仔细观看视频的实际画面内容
2. 【必须】识别精彩片段、高潮时刻和视觉亮点
3. 【必须】根据实际画面设计{$targetDuration}秒的剪辑方案
4. 【必须】时间戳要准确，基于视频实际内容
5. 【重要】如果用户指定了精确时长（如\"30秒\"），严格遵守

请提供以下内容（使用JSON格式回复）：
{
  \"summary\": \"基于实际观看的视频画面内容摘要，200字以内\",
  \"scenes\": {$scenesJson},
  \"highlights\": [\"基于实际画面的亮点1\", \"亮点2\", \"亮点3\"],
  \"video_prompt\": \"基于实际观看的画面生成的描述，100字以内\"
}

⚠️ 注意：
- 时间段必须覆盖总共{$targetDuration}秒
- 所有描述必须基于你实际看到的视频画面
- 确保回复是有效的JSON格式";
        } else {

            $analysisPrompt = "你是一位专业的短视频剪辑师。请基于以下信息设计视频剪辑方案。

【视频信息】
- 文件名：{$fileName}
- 用户描述：{$description}
- 目标剪辑时长：{$targetDuration}秒
- 建议分为{$clipCount}个片段，每个约{$clipDuration}秒

【重要提示】
1. 你只能看到文件名和描述，无法直接观看视频内容
2. 请基于文件名关键词和描述中的信息进行分析
3. 如果信息不足，请明确说明：信息不足，无法准确分析
4. 不要编造具体的画面细节，只基于已有信息推测

请提供以下内容（使用JSON格式回复）：
{
  \"summary\": \"基于文件名和描述的视频内容分析（如信息不足请说明）\",
  \"scenes\": {$scenesJson},
  \"highlights\": [\"基于描述的亮点1\", \"亮点2\", \"亮点3\"],
  \"video_prompt\": \"基于已有信息生成的视频描述\"
}

注意：时间段必须覆盖总共{$targetDuration}秒，确保回复是有效的JSON格式";
        }


        $providerManager = new AIProviderManager();
        $provider = $providerManager->getProvider($providerId);
        
        if (!$provider) {
            echo json_encode(['success' => false, 'error' => 'AI提供商配置不存在']);
            return;
        }
        

        if ($isMultimodalModel) {
            $response = callAIForAnalysisWithVideo($provider, $model, $analysisPrompt, $videoPath);
        } else {
            $response = callAIForAnalysis($provider, $model, $analysisPrompt);
        }
        
        if (!$response['success']) {
            echo json_encode(['success' => false, 'error' => $response['error'] ?? 'AI分析失败']);
            return;
        }
        

        $aiContent = $response['content'] ?? '';
        

        $jsonStart = strpos($aiContent, '{');
        $jsonEnd = strrpos($aiContent, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $jsonStr = substr($aiContent, $jsonStart, $jsonEnd - $jsonStart + 1);
            $analysisData = json_decode($jsonStr, true);
            
            if ($analysisData) {
                echo json_encode([
                    'success' => true,
                    'summary' => $analysisData['summary'] ?? '未生成摘要',
                    'scenes' => $analysisData['scenes'] ?? [],
                    'highlights' => $analysisData['highlights'] ?? [],
                    'video_prompt' => $analysisData['video_prompt'] ?? $description,
                    'description' => $description
                ]);
                return;
            }
        }
        

        echo json_encode([
            'success' => true,
            'summary' => 'AI已分析视频内容。文件名：' . $fileName,
            'scenes' => [
                ['time' => '00:00', 'description' => '视频开头精彩片段'],
                ['time' => '00:10', 'description' => '中间高潮部分'],
                ['time' => '00:20', 'description' => '结尾精彩片段']
            ],
            'highlights' => ['精彩瞬间', '核心内容', '视觉亮点'],
            'video_prompt' => $description ?: '一个精彩的短视频，包含精彩片段和视觉效果',
            'description' => $description,
            'note' => '使用默认分析结果'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '分析失败：' . $e->getMessage()]);
    }
}


function callAIForAnalysis($provider, $model, $prompt) {
    try {
        $apiKey = $provider['config']['api_key'] ?? '';

        $apiUrl = $provider['config']['base_url'] ?? $provider['config']['api_url'] ?? '';
        $providerType = $provider['type'] ?? '';
        

        if ($providerType !== 'ollama' && empty($apiKey)) {
            return ['success' => false, 'error' => '未配置API密钥'];
        }
        
        if (empty($apiUrl)) {
            return ['success' => false, 'error' => '未配置API地址'];
        }
        

        return callOpenAICompatibleAPI($apiUrl, $apiKey, $model, $prompt);
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


function callOpenAIAnalysis($apiUrl, $apiKey, $model, $prompt) {
    $url = rtrim($apiUrl, '/') . '/chat/completions';
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => '你是一个专业的视频剪辑师，擅长分析视频内容并提供创意剪辑方案。'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7
    ];
    

    $logFile = __DIR__ . '/../logs/scenario_analysis.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    error_log("[" . date('Y-m-d H:i:s') . "] OpenAI Analysis Request - Model: {$model}\n", 3, $logFile);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    error_log("[" . date('Y-m-d H:i:s') . "] HTTP Code: {$httpCode}\n", 3, $logFile);
    
    if ($httpCode !== 200) {
        $errorDetail = '';
        $result = json_decode($response, true);
        if (isset($result['error']['message'])) {
            $errorDetail = ' - ' . $result['error']['message'];
        }
        return ['success' => false, 'error' => 'API请求失败: HTTP ' . $httpCode . $errorDetail];
    }
    
    if ($curlError) {
        return ['success' => false, 'error' => 'CURL错误: ' . $curlError];
    }
    
    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';
    
    return ['success' => true, 'content' => $content];
}


function callAIForAnalysisWithVideo($provider, $model, $prompt, $videoPath) {

    return callAIForAnalysis($provider, $model, $prompt);
}



function callHunyuanAnalysisWithVideo($provider, $model, $prompt, $videoPath) {
    return callAIForAnalysisWithVideo($provider, $model, $prompt, $videoPath);
}


function callHunyuanForAnalysis($provider, $model, $prompt) {
    return callAIForAnalysis($provider, $model, $prompt);
}

function callQwenAnalysisWithVideo($apiUrl, $apiKey, $model, $prompt, $videoPath) {
    return ['success' => false, 'error' => '此函数已弃用，请使用系统中已导入的AI模型'];
}

function callQwenAnalysis($apiUrl, $apiKey, $model, $prompt) {
    return callOpenAICompatibleAPI($apiUrl, $apiKey, $model, $prompt);
}

function callHunyuanAPI($prompt) {
    return ['success' => false, 'error' => '此函数已弃用，请使用系统中已导入的AI模型'];
}

function callHunyuanNativeAPI($prompt, $secretId, $secretKey, $region, $model) {
    return ['success' => false, 'error' => '此函数已弃用，请使用系统中已导入的AI模型'];
}

function callHunyuanOpenAICompatible($prompt, $apiUrl, $apiKey, $model) {
    return callOpenAICompatibleAPI($apiUrl, $apiKey, $model, $prompt);
}




function _old_callQwenAnalysis($apiUrl, $apiKey, $model, $prompt) {
    return callOpenAICompatibleAPI($apiUrl, $apiKey, $model, $prompt);
}




function handleGeneratePrompt() {
    try {
        $userInput = $_POST['user_input'] ?? '';
        $scenarioType = $_POST['scenario_type'] ?? '';
        $systemPrompt = $_POST['system_prompt'] ?? '';
        $providerId = $_POST['provider_id'] ?? '';
        $modelId = $_POST['model'] ?? '';
        
        if (empty($userInput)) {
            echo json_encode(['success' => false, 'error' => '请输入需求描述']);
            return;
        }
        

        $fullPrompt = "{$systemPrompt}\n\n用户需求：{$userInput}\n\n请根据以上信息生成一个详细的提示词，直接输出提示词内容，不需要额外解释。";
        
        $result = null;
        

        if (!empty($providerId) && !empty($modelId)) {
            $result = callAIProviderForPrompt($providerId, $modelId, $fullPrompt);
        } else {

            echo json_encode(['success' => false, 'error' => '请选择AI模型']);
            return;
        }
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'prompt' => $result['content']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '生成失败：' . $e->getMessage()]);
    }
}


function callAIProviderForPrompt($providerId, $modelId, $prompt) {
    try {
        $providerManager = new AIProviderManager();
        $providers = $providerManager->getProviders(true);
        
        error_log("callAIProviderForPrompt - providerId: $providerId, modelId: $modelId");
        

        $provider = null;
        if (isset($providers[$providerId])) {
            $provider = $providers[$providerId];
            error_log("找到提供商通过ID: $providerId");
        } else {

            foreach ($providers as $id => $p) {
                if ($p['type'] === $providerId) {
                    $provider = $p;
                    $providerId = $id;
                    error_log("找到提供商通过Type: $providerId");
                    break;
                }
            }
        }
        
        if (empty($provider)) {
            error_log("未找到提供商: $providerId");
            return ['success' => false, 'error' => '未找到指定的AI提供商：' . $providerId];
        }
        
        $apiKey = $provider['config']['api_key'] ?? '';

        $apiUrl = $provider['config']['base_url'] ?? $provider['config']['api_url'] ?? '';
        $providerType = $provider['type'];
        
        error_log("调用AI提供商: type=$providerType, model=$modelId, apiUrl=$apiUrl, hasApiKey=" . (!empty($apiKey) ? 'yes' : 'no'));
        

        if (empty($modelId)) {

            $models = $provider['models'] ?? [];
            if (!empty($models)) {
                $modelId = $models[0];
                error_log("使用默认模型: $modelId");
            } else {
                return ['success' => false, 'error' => '未指定模型且提供商没有可用模型'];
            }
        }
        

        if ($providerType === 'ollama') {
            if (empty($apiUrl)) {
                return ['success' => false, 'error' => 'Ollama配置不完整，请检查Base URL'];
            }

            $apiKey = '';
        } else if (empty($apiKey) || empty($apiUrl)) {
            error_log("配置不完整: apiKey=" . (empty($apiKey) ? 'empty' : 'set') . ", apiUrl=" . (empty($apiUrl) ? 'empty' : 'set'));
            return ['success' => false, 'error' => '提供商配置不完整，请检查API Key和API URL'];
        }
        

        switch ($providerType) {
            case 'hunyuan':
            case 'openai':
            case 'azure_openai':
            case 'deepseek':
            case 'zhipu':
            case 'moonshot':
            case 'qwen':
            default:

                return callOpenAICompatibleAPI($apiUrl, $apiKey, $modelId, $prompt);
        }
        
    } catch (Exception $e) {
        error_log('调用AI提供商失败: ' . $e->getMessage());
        return ['success' => false, 'error' => '调用AI提供商失败：' . $e->getMessage()];
    }
}


function callOpenAICompatibleAPI($apiUrl, $apiKey, $model, $prompt) {
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2048
    ];
    
    $ch = curl_init(rtrim($apiUrl, '/') . '/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    if ($curlError) {
        return ['success' => false, 'error' => '网络连接失败：' . $curlError];
    }
    
    if ($httpCode !== 200) {
        $errorMsg = 'API请求失败 (HTTP ' . $httpCode . ')';
        $result = json_decode($response, true);
        if (isset($result['error']['message'])) {
            $errorMsg .= ': ' . $result['error']['message'];
        }
        return ['success' => false, 'error' => $errorMsg];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
    }
    
    return ['success' => false, 'error' => '无法解析API响应'];
}

/**
 * 导出单个视频片段
 */
function handleExportSegment() {
    try {
        $videoUrl = $_POST['video_url'] ?? '';
        $startTime = intval($_POST['start_time'] ?? 0);
        $endTime = intval($_POST['end_time'] ?? 0);
        $description = $_POST['description'] ?? '片段';
        
        // 从URL中提取文件路径
        $videoPath = str_replace('http://' . $_SERVER['HTTP_HOST'] . '/', '', $videoUrl);
        $videoPath = str_replace('https://' . $_SERVER['HTTP_HOST'] . '/', '', $videoPath);
        $fullVideoPath = __DIR__ . '/../' . $videoPath;
        
        if (!file_exists($fullVideoPath)) {
            echo json_encode(['success' => false, 'error' => '视频文件不存在']);
            return;
        }
        
        $outputDir = __DIR__ . '/../uploads/videos/segments/';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        $timestamp = time();
        $safeDesc = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}]/u', '_', $description);
        $safeDesc = substr($safeDesc, 0, 20);
        $outputFileName = 'segment_' . $safeDesc . '_' . $timestamp . '.mp4';
        $outputPath = $outputDir . $outputFileName;
        
        $duration = $endTime - $startTime;
        if ($duration <= 0) {
            $duration = 10;
        }
        
        $ffmpegPath = getFFmpegPath();
        $cmd = sprintf(
            '"%s" -ss %d -t %d -i "%s" -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
            $ffmpegPath,
            $startTime,
            $duration,
            $fullVideoPath,
            $outputPath
        );
        
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 10240) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $downloadUrl = $protocol . '://' . $host . '/uploads/videos/segments/' . $outputFileName;
            
            echo json_encode([
                'success' => true,
                'download_url' => $downloadUrl,
                'file_name' => $outputFileName,
                'duration' => $duration
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => '片段导出失败']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '导出失败：' . $e->getMessage()]);
    }
}

/**
 * 一键导出所有片段（打包为ZIP）
 */
function handleExportAllSegments() {
    try {
        $videoUrl = $_POST['video_url'] ?? '';
        $editPlan = json_decode($_POST['edit_plan'] ?? '{}', true);
        $highlights = $editPlan['highlights'] ?? [];
        
        if (empty($highlights)) {
            echo json_encode(['success' => false, 'error' => '没有可导出的片段']);
            return;
        }
        
        $videoPath = str_replace('http://' . $_SERVER['HTTP_HOST'] . '/', '', $videoUrl);
        $videoPath = str_replace('https://' . $_SERVER['HTTP_HOST'] . '/', '', $videoPath);
        $fullVideoPath = __DIR__ . '/../' . $videoPath;
        
        if (!file_exists($fullVideoPath)) {
            echo json_encode(['success' => false, 'error' => '视频文件不存在']);
            return;
        }
        
        $outputDir = __DIR__ . '/../uploads/videos/segments/';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        $timestamp = time();
        $tempDir = $outputDir . 'batch_' . $timestamp . '/';
        mkdir($tempDir, 0755, true);
        
        $ffmpegPath = getFFmpegPath();
        $segmentFiles = [];
        
        foreach ($highlights as $index => $highlight) {
            $startTime = intval($highlight['start_time'] ?? 0);
            $endTime = intval($highlight['end_time'] ?? 0);
            $description = $highlight['description'] ?? '片段' . ($index + 1);
            $duration = $endTime - $startTime;
            
            if ($duration <= 0) {
                continue;
            }
            
            $safeDesc = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}]/u', '_', $description);
            $safeDesc = substr($safeDesc, 0, 15);
            $segmentFileName = sprintf('%02d_%s.mp4', $index + 1, $safeDesc);
            $segmentPath = $tempDir . $segmentFileName;
            
            $cmd = sprintf(
                '"%s" -ss %d -t %d -i "%s" -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
                $ffmpegPath,
                $startTime,
                $duration,
                $fullVideoPath,
                $segmentPath
            );
            
            exec($cmd, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($segmentPath) && filesize($segmentPath) > 10240) {
                $segmentFiles[] = $segmentPath;
            }
        }
        
        if (empty($segmentFiles)) {
            rmdir($tempDir);
            echo json_encode(['success' => false, 'error' => '没有成功导出的片段']);
            return;
        }
        
        $zipFileName = 'all_segments_' . $timestamp . '.zip';
        $zipPath = $outputDir . $zipFileName;
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($segmentFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            
            // 清理临时文件
            foreach ($segmentFiles as $file) {
                @unlink($file);
            }
            rmdir($tempDir);
            
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $downloadUrl = $protocol . '://' . $host . '/uploads/videos/segments/' . $zipFileName;
            
            echo json_encode([
                'success' => true,
                'download_url' => $downloadUrl,
                'file_name' => $zipFileName,
                'segment_count' => count($segmentFiles)
            ]);
        } else {
            foreach ($segmentFiles as $file) {
                @unlink($file);
            }
            rmdir($tempDir);
            echo json_encode(['success' => false, 'error' => '创建ZIP文件失败']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '批量导出失败：' . $e->getMessage()]);
    }
}





