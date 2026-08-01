import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const ignored = new Set(['data', '.git']);

function walk(directory) {
  const files = [];
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    if (ignored.has(entry.name)) continue;
    const fullPath = path.join(directory, entry.name);
    if (entry.isDirectory()) files.push(...walk(fullPath));
    else files.push(fullPath);
  }
  return files;
}

const files = walk(root);
const errors = [];
if (fs.existsSync(path.join(root, 'tools', 'unit-convert.php'))) {
  errors.push('tools/unit-convert.php：旧版重复单位换算页面仍未移除');
}

for (const file of files.filter((name) => name.endsWith('.js'))) {
  const source = fs.readFileSync(file, 'utf8');
  try {
    new Function(source);
  } catch (error) {
    errors.push(`${path.relative(root, file)}：JavaScript 语法错误：${error.message}`);
  }
}

for (const file of files.filter((name) => name.endsWith('.php'))) {
  const source = fs.readFileSync(file, 'utf8');
  const scriptPattern = /<script([^>]*)>([\s\S]*?)<\/script>/gi;
  let match;
  let index = 0;
  while ((match = scriptPattern.exec(source)) !== null) {
    index += 1;
    const attributes = match[1] || '';
    const script = match[2] || '';
    if (/\bsrc\s*=/i.test(attributes) || script.includes('<?')) continue;
    try {
      new Function(script);
    } catch (error) {
      errors.push(`${path.relative(root, file)}：第 ${index} 个内联脚本语法错误：${error.message}`);
    }
  }
}

const cssFile = path.join(root, 'css', 'style.css');
const css = fs.readFileSync(cssFile, 'utf8');
let braceDepth = 0;
for (const character of css) {
  if (character === '{') braceDepth += 1;
  if (character === '}') braceDepth -= 1;
  if (braceDepth < 0) break;
}
if (braceDepth !== 0) errors.push(`css/style.css：大括号不匹配（${braceDepth}）`);
for (const marker of ['max-width: 1680px', 'repeat(auto-fit, minmax(180px, 240px))', 'grid-auto-rows: minmax(138px, auto)', 'justify-content: center', 'min-height:138px', 'height:auto']) {
  if (!css.includes(marker)) errors.push(`css/style.css：宽屏比例修复缺少 ${marker}`);
}

const homepage = fs.readFileSync(path.join(root, 'index.php'), 'utf8');
const toolLinkPattern = /href=["'](tools\/[^"'?#]+\.php)(?:[?#][^"']*)?["']/g;
const checkedLinks = new Set();
let linkMatch;
while ((linkMatch = toolLinkPattern.exec(homepage)) !== null) {
  const relative = linkMatch[1];
  if (checkedLinks.has(relative)) continue;
  checkedLinks.add(relative);
  if (!fs.existsSync(path.join(root, relative))) errors.push(`index.php：工具入口不存在：${relative}`);
}

const officeTools = new Map([
  ['tools/date-calculator.php', ['工作日', 'offsetMode', '数据仅在浏览器中处理']],
  ['tools/unit-converter.php', ['temperature', 'storage', '交换单位']],
  ['tools/percentage-calculator.php', ['涨跌幅', 'changeRate', '不能除以 0']],
  ['tools/text-diff.php', ['compareLines', 'replaceChildren', '不会上传服务器']],
  ['tools/random.php', ['cryptoApi.getRandomValues', 'randomBelow', 'sampleUnique', 'secureShuffle', 'cryptoApi.randomUUID', '排除易混淆字符', '不会上传服务器']],
]);
for (const [relative, markers] of officeTools) {
  const source = fs.readFileSync(path.join(root, relative), 'utf8');
  for (const marker of markers) {
    if (!source.includes(marker)) errors.push(`${relative}：办公辅助功能缺少 ${marker}`);
  }
}
const randomTool = fs.readFileSync(path.join(root, 'tools', 'random.php'), 'utf8');
if (randomTool.includes('Math.random(')) errors.push('tools/random.php：安全随机工具仍在使用 Math.random()');

const documentTools = new Map([
  ['tools/csv-helper.php', ['parseCsv', 'parseJson', 'maxCells=250000', 'spreadsheetSafe', 'protectFormula', '\\uFF1D', 'downloadJson', 'Object.create(null)', 'URL.revokeObjectURL', '文件只在浏览器中处理']],
  ['tools/rmb-uppercase.php', ['integerText', '999999999999.99', '正式票据请再次核对']],
  ['tools/rich-text-editor.php', ['safeHtml', "localStorage.setItem(storageKey,safeHtml())", "addEventListener('drop'", '粘贴内容会转为纯文本']],
  ['tools/json-formatter.php', ['JSON.parse', 'maxNodes=100000', 'maxDepth=100', 'errorLocation', 'sortValue', 'URL.revokeObjectURL', '内容只在浏览器中处理']],
]);
for (const [relative, markers] of documentTools) {
  const source = fs.readFileSync(path.join(root, relative), 'utf8');
  for (const marker of markers) {
    if (!source.includes(marker)) errors.push(`${relative}：表格文档功能缺少 ${marker}`);
  }
}

const productivityTools = new Map([
  ['tools/signature-pad.php', ["addEventListener('pointerdown'", 'ResizeObserver', 'getCoalescedEvents', 'MAX_STROKES=500', 'MAX_POINTS=100000', 'normalizedPoint', 'clearedStrokes', 'strokes.pop()', 'toBlob', '签名不会上传服务器']],
  ['tools/screen-recorder.php', ['getDisplayMedia', 'MediaRecorder.isTypeSupported', 'MAX_RECORDING_BYTES=500*1024*1024', 'MAX_RECORDING_SECONDS=3600', 'videoBitsPerSecond', "addEventListener('error'", "stop('size-limit')", 'releaseResult', "includes('mp4')", 'URL.revokeObjectURL', '录制内容不会上传服务器']],
  ['tools/pomodoro.php', ['deadline-Date.now()', 'office_pomodoro_stats', 'Notification.requestPermission', 'setPhase(phase===', 'completed']],
]);
for (const [relative, markers] of productivityTools) {
  const source = fs.readFileSync(path.join(root, relative), 'utf8');
  for (const marker of markers) {
    if (!source.includes(marker)) errors.push(`${relative}：效率演示功能缺少 ${marker}`);
  }
}
const signatureTool = fs.readFileSync(path.join(root, 'tools', 'signature-pad.php'), 'utf8');
if (signatureTool.includes('canvas.toDataURL') || signatureTool.includes('history.push')) {
  errors.push('tools/signature-pad.php：签名撤销仍在复制整张 Base64 画布');
}

const dockerfile = fs.readFileSync(path.join(root, 'Dockerfile'), 'utf8');
if (!dockerfile.includes('FROM node:24-alpine AS frontend-assets')) {
  errors.push('Dockerfile：前端构建环境未使用 Node.js 24');
}
const requiredPdfAssets = [
  'static/vendor/pdf.min.js',
  'static/vendor/pdf.worker.min.js',
  'static/vendor/pdf-lib.min.js',
  'static/vendor/jspdf.umd.min.js',
  'static/vendor/jszip.min.js',
  'static/vendor/qrcode.js',
];
for (const asset of requiredPdfAssets) {
  if (!dockerfile.includes(asset)) errors.push(`Dockerfile：未打包离线 PDF 组件 ${asset}`);
}

const compose = fs.readFileSync(path.join(root, 'docker-compose.yml'), 'utf8');
if (/^version\s*:/m.test(compose)) {
  errors.push('docker-compose.yml：仍包含新版 Compose 已废弃的 version 字段');
}

const dockerWorkflow = fs.readFileSync(path.join(root, '.github', 'workflows', 'docker-build.yml'), 'utf8');
for (const marker of [
  'actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1',
  'shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240',
  'docker/setup-buildx-action@bb05f3f5519dd87d3ba754cc423b652a5edd6d2c',
  'docker/build-push-action@53b7df96c91f9c12dcc8a07bcb9ccacbed38856a',
  'docker/login-action@dbcb813823bdd20940b903addbd779551569679f',
  'load: true',
  'cache-from: type=gha',
  'cache-to: type=gha,mode=max,ignore-error=true',
  'api/health.php',
  'tools/pdf-organize.php',
  'tools/pdf-page-numbers.php',
  'tools/pdf-metadata.php',
  'tools/pdf-to-text.php',
  'tools/signature-pad.php',
  'MAX_POINTS=100000',
  'tools/screen-recorder.php',
  'MAX_RECORDING_BYTES=500',
  'api/media.php?action=process',
  'media-smoke.mp4',
  'idcard-smoke.png',
  'api/upload.php?action=get',
  'cmp idcard-smoke.png idcard-downloaded.png',
  'docker rm --force office-web-smoke',
]) {
  if (!dockerWorkflow.includes(marker)) errors.push(`Docker 工作流：构建安全或运行检查缺少 ${marker}`);
}

const pdfToolRequirements = new Map([
  ['tools/pdf-merge.php', ['../static/vendor/pdf-lib.min.js']],
  ['tools/pdf-split.php', ['../static/vendor/pdf-lib.min.js']],
  ['tools/pdf-organize.php', ['../static/vendor/pdf.min.js', '../static/vendor/pdf.worker.min.js', '../static/vendor/pdf-lib.min.js']],
  ['tools/pdf-page-numbers.php', ['../static/vendor/pdf.min.js', '../static/vendor/pdf.worker.min.js', '../static/vendor/pdf-lib.min.js']],
  ['tools/pdf-metadata.php', ['../static/vendor/pdf-lib.min.js']],
  ['tools/pdf-to-text.php', ['../static/vendor/pdf.min.js', '../static/vendor/pdf.worker.min.js']],
  ['tools/pdf-compress.php', ['../static/vendor/pdf.min.js', '../static/vendor/pdf.worker.min.js', '../static/vendor/jspdf.umd.min.js']],
  ['tools/pdf-watermark.php', ['../static/vendor/pdf.min.js', '../static/vendor/pdf.worker.min.js', '../static/vendor/pdf-lib.min.js']],
  ['tools/pdf-to-image.php', ['../static/vendor/pdf.min.js', '../static/vendor/pdf.worker.min.js', '../static/vendor/jszip.min.js']],
  ['tools/images-to-pdf.php', ['../static/vendor/jspdf.umd.min.js']],
]);
for (const [relative, assets] of pdfToolRequirements) {
  const source = fs.readFileSync(path.join(root, relative), 'utf8');
  for (const asset of assets) {
    if (!source.includes(asset)) errors.push(`${relative}：未优先使用离线组件 ${asset}`);
  }
}
const pdfToImage = fs.readFileSync(path.join(root, 'tools', 'pdf-to-image.php'), 'utf8');
if (pdfToImage.includes('jspdf.umd')) errors.push('tools/pdf-to-image.php：仍在加载未使用的 jsPDF 组件');
for (const marker of ['new window.JSZip()', "compression: 'STORE'", "a.download = 'pdf-images.zip'", 'URL.revokeObjectURL']) {
  if (!pdfToImage.includes(marker)) errors.push(`tools/pdf-to-image.php：ZIP 打包或内存释放缺少 ${marker}`);
}

for (const relative of ['tools/qrcode.php', 'tools/idcard-print.php']) {
  const source = fs.readFileSync(path.join(root, relative), 'utf8');
  if (!source.includes('../static/vendor/qrcode.js')) errors.push(`${relative}：未优先使用本地二维码组件`);
  if (source.includes('api.qrserver.com')) errors.push(`${relative}：仍会把二维码内容发送到第三方接口`);
}

const uploadApi = fs.readFileSync(path.join(root, 'api', 'upload.php'), 'utf8');
for (const marker of ['validSession', "['front', 'back']", "$uploadRoot . $session", 'MAX_IMAGE_PIXELS', 'MAX_IMAGE_SIDE', 'RATE_MAX_UPLOADS', 'enforceUploadRate', 'getimagesize', "header('Content-Length: '", '@readfile($filePath)']) {
  if (!uploadApi.includes(marker)) errors.push(`api/upload.php：身份证上传隔离缺少 ${marker}`);
}
if (uploadApi.includes('base64_encode($data)')) errors.push('api/upload.php：身份证图片下载仍在使用 Base64 JSON');
const idcardMobile = fs.readFileSync(path.join(root, 'tools', 'idcard-mobile.php'), 'utf8');
for (const marker of ["formData.append('session', uploadSession)", 'allowedImageTypes', 'MAX_IMAGE_PIXELS', 'URL.revokeObjectURL', 'controller.abort()', 'clearSelection']) {
  if (!idcardMobile.includes(marker)) errors.push(`tools/idcard-mobile.php：手机上传保护缺少 ${marker}`);
}
if (idcardMobile.includes('readAsDataURL') || idcardMobile.includes('preview.innerHTML')) errors.push('tools/idcard-mobile.php：手机预览仍在使用高内存或动态 HTML 流程');
const idcardPrint = fs.readFileSync(path.join(root, 'tools', 'idcard-print.php'), 'utf8');
for (const marker of [
  "searchParams.set('session', uploadSession)",
  'action=list&session=',
  'action=get&session=',
  "deleteData.append('session', uploadSession)",
]) {
  if (!idcardPrint.includes(marker)) errors.push(`tools/idcard-print.php：电脑同步隔离缺少 ${marker}`);
}
for (const marker of ['mobileBaseUrl', 'refreshQr', 'isLoopbackHost', 'mobileAddressStorageKey']) {
  if (!idcardPrint.includes(marker)) errors.push(`tools/idcard-print.php：局域网扫码地址设置缺少 ${marker}`);
}
for (const marker of ['validatedImageUrl', 'MAX_IMAGE_PIXELS', 'managedImageUrls', 'imgResponse.blob()', 'canvas.toBlob', 'releaseManagedImages', 'watermark.textContent', 'syncedFiles', 'fetchWithTimeout', 'result.files.slice().reverse()']) {
  if (!idcardPrint.includes(marker)) errors.push(`tools/idcard-print.php：身份证图片内存或打印保护缺少 ${marker}`);
}
if (idcardPrint.includes('reader.readAsDataURL') || idcardPrint.includes("canvas.toDataURL('image/png')") || idcardPrint.includes("placeholder.innerHTML = '<img")) {
  errors.push('tools/idcard-print.php：身份证打印仍在使用高内存或动态图片 HTML 流程');
}

const exchangeApi = fs.readFileSync(path.join(root, 'api', 'exchange.php'), 'utf8');
for (const marker of ['RATE_CACHE_TTL', 'exchange-rates.json', 'api.frankfurter.dev/v2/rates', "'stale'"]) {
  if (!exchangeApi.includes(marker)) errors.push(`api/exchange.php：汇率缓存接口缺少 ${marker}`);
}
const currencyTool = fs.readFileSync(path.join(root, 'tools', 'currency-convert.php'), 'utf8');
for (const marker of ['api/exchange.php', 'rateStatus', '本机缓存', '内置参考值']) {
  if (!currencyTool.includes(marker)) errors.push(`tools/currency-convert.php：汇率状态展示缺少 ${marker}`);
}
const mainScript = fs.readFileSync(path.join(root, 'js', 'main.js'), 'utf8');
if (mainScript.includes('api.open-meteo.com')) errors.push('js/main.js：仍包含已废弃的固定北京天气请求');

const mediaApi = fs.readFileSync(path.join(root, 'api', 'media.php'), 'utf8');
for (const marker of ['MAX_VIDEO_DURATION', 'MAX_VIDEO_PIXELS', 'MAX_OUTPUT_BYTES', 'RATE_MAX_ATTEMPTS', 'enforce_media_rate', "media_binary('ffprobe')", '--kill-after=5s 115s', '-fs ', '[124, 137]']) {
  if (!mediaApi.includes(marker)) errors.push(`api/media.php：视频资源保护缺少 ${marker}`);
}
const healthApi = fs.readFileSync(path.join(root, 'api', 'health.php'), 'utf8');
for (const marker of ["'ffprobe'", "'timeout'", "glob($mediaDir . '/media_*')", 'time() - 60 * 60']) {
  if (!healthApi.includes(marker)) errors.push(`api/health.php：视频依赖健康检查缺少 ${marker}`);
}
const videoTool = fs.readFileSync(path.join(root, 'tools', 'video-process.php'), 'utf8');
for (const marker of ['xhr.timeout=250000', 'xhr.ontimeout']) {
  if (!videoTool.includes(marker)) errors.push(`tools/video-process.php：视频请求超时反馈缺少 ${marker}`);
}

const transferApi = fs.readFileSync(path.join(root, 'api', 'transfer.php'), 'utf8');
for (const marker of ["$ttl = 10 * 60", "random_int(0, 999999)", 'enforceAttemptLimit', 'validStoredName']) {
  if (!transferApi.includes(marker)) errors.push(`api/transfer.php：文件传输保护缺少 ${marker}`);
}
if (transferApi.includes("preg_match('/^\\d{4}$/', $code)")) errors.push('api/transfer.php：仍接受旧的4位提取码');
if (!homepage.includes('maxlength="6"') || homepage.includes('4 位提取码')) {
  errors.push('index.php：文件传输界面未完整切换为6位提取码');
}
if (!mainScript.includes("/^\\d{6}$/.test(code)") || !mainScript.includes("slice(0, 6)")) {
  errors.push('js/main.js：文件接收校验未切换为6位提取码');
}
if (!homepage.includes('send-result-expiry') || !mainScript.includes('startExpiryCountdown(data.expires_in)')) {
  errors.push('文件传输：提取码有效期倒计时不完整');
}

const chatApi = fs.readFileSync(path.join(root, 'api', 'chat.php'), 'utf8');
for (const marker of ['chat-rates.json', 'enforce_rate_limit', "'send', 20, 30", "'heartbeat', 120, 60", 'count($online) >= 200']) {
  if (!chatApi.includes(marker)) errors.push(`api/chat.php：聊天室保护缺少 ${marker}`);
}
if (mainScript.includes("Math.random().toString(36)")) errors.push('js/main.js：聊天室仍使用弱随机用户标识');
if (!mainScript.includes('window.crypto.getRandomValues(uidBytes)') || !mainScript.includes('chatMessages.slice(-100)')) {
  errors.push('js/main.js：聊天室随机标识或前端消息上限不完整');
}

const markdownTool = fs.readFileSync(path.join(root, 'tools', 'text-markdown.php'), 'utf8');
for (const marker of ['safeMarkdownUrl', 'blocked-link', 'noopener noreferrer']) {
  if (!markdownTool.includes(marker)) errors.push(`tools/text-markdown.php：Markdown 链接保护缺少 ${marker}`);
}
const timerTool = fs.readFileSync(path.join(root, 'tools', 'timer.php'), 'utf8');
if (timerTool.includes('taskDiv.innerHTML') || !timerTool.includes("document.createTextNode(' ' + String(task.text || ''))")) {
  errors.push('tools/timer.php：任务名称仍可能作为 HTML 执行');
}
const pdfMergeTool = fs.readFileSync(path.join(root, 'tools', 'pdf-merge.php'), 'utf8');
if (pdfMergeTool.includes("$('fileList').innerHTML") || !pdfMergeTool.includes('row.textContent')) {
  errors.push('tools/pdf-merge.php：PDF 文件名仍可能作为 HTML 执行');
}
const imageBase64Tool = fs.readFileSync(path.join(root, 'tools', 'image-base64.php'), 'utf8');
for (const marker of ['allowedImageTypes', 'maxDataUrlLength', 'validImageDataUrl']) {
  if (!imageBase64Tool.includes(marker)) errors.push(`tools/image-base64.php：Base64 图片校验缺少 ${marker}`);
}

const imageToPdfTool = fs.readFileSync(path.join(root, 'tools', 'images-to-pdf.php'), 'utf8');
for (const marker of ['combined.length>30', '100*1024*1024', 'maxSide=4000', 'pdf.addImage', 'URL.revokeObjectURL']) {
  if (!imageToPdfTool.includes(marker)) errors.push(`tools/images-to-pdf.php：图片转 PDF 保护缺少 ${marker}`);
}
const imageStitchTool = fs.readFileSync(path.join(root, 'tools', 'image-stitch.php'), 'utf8');
for (const marker of ['selected.length>20', '80000000', '30000/width', 'resultFormat', 'URL.revokeObjectURL', '图片只在浏览器中处理']) {
  if (!imageStitchTool.includes(marker)) errors.push(`tools/image-stitch.php：图片拼接保护缺少 ${marker}`);
}

const hardenedImageTools = new Map([
  ['tools/image-compress.php', ['allowedTypes', '40 * 1024 * 1024', '50000000', 'fillRect(0, 0, canvas.width, canvas.height)', 'beforeunload', 'revoke(previewUrl)']],
  ['tools/image-format.php', ['allowedTypes', '40 * 1024 * 1024', '50000000', 'beforeunload', 'revoke(previewUrl)']],
  ['tools/image-edit.php', ['MAX_HISTORY = 15', '25 * 1024 * 1024', '30000000', 'URL.revokeObjectURL(sourceUrl)', 'setTimeout(() => URL.revokeObjectURL(url), 1000)']],
  ['tools/image-resize.php', ['allowedTypes', '40 * 1024 * 1024', '50000000', 'MAX_SIDE = 16384', 'canvas.toBlob', 'URL.revokeObjectURL', 'beforeunload', '文件只在浏览器中处理']],
  ['tools/image-crop.php', ['allowedTypes', '40 * 1024 * 1024', '50000000', 'MAX_SIDE = 16384', 'canvas.toBlob', 'URL.revokeObjectURL', '裁剪范围不能超出原图', 'beforeunload', '文件只在浏览器中处理']],
  ['tools/image-watermark.php', ['allowedTypes', '40 * 1024 * 1024', '50000000', 'MAX_SIDE = 16384', 'canvas.toBlob', 'URL.revokeObjectURL', 'safeFontSize', 'beforeunload', '文件只在浏览器中处理']],
]);
for (const [relative, markers] of hardenedImageTools) {
  const source = fs.readFileSync(path.join(root, relative), 'utf8');
  for (const marker of markers) {
    if (!source.includes(marker)) errors.push(`${relative}：图片内存保护缺少 ${marker}`);
  }
}
for (const relative of ['tools/image-resize.php', 'tools/image-crop.php', 'tools/image-watermark.php']) {
  const source = fs.readFileSync(path.join(root, relative), 'utf8');
  if (source.includes('readAsDataURL') || source.includes('resultDataURL')) {
    errors.push(`${relative}：仍在使用高内存 Base64 图片流程`);
  }
}
const hardenedPdfTools = new Map([
  ['tools/pdf-merge.php', ['selectedFiles.length > 20', '150 * 1024 * 1024', 'setTimeout(() => URL.revokeObjectURL(url), 1000)']],
  ['tools/pdf-split.php', ['120 * 1024 * 1024', 'setTimeout(() => URL.revokeObjectURL(url), 1000)']],
  ['tools/pdf-organize.php', ['120*1024*1024', 'MAX_PAGES=150', 'sourceBytes.slice(0)', 'page.setRotation', 'copyPages', 'URL.revokeObjectURL(url)', '文件只在浏览器中处理']],
  ['tools/pdf-page-numbers.php', ['120*1024*1024', 'MAX_PAGES=500', 'visualToPdf', 'page.getRotation()', 'safeFontSize', 'StandardFonts.Helvetica', '预览暂不可用，但可正常下载', 'URL.revokeObjectURL(url)', '文件只在浏览器中处理']],
  ['tools/pdf-metadata.php', ['120*1024*1024', 'MAX_PAGES=1000', 'updateMetadata:false', 'pdf.getTitle()', 'pdf.setTitle', 'pdf.setKeywords', 'slice(0,500)', 'slice(0,50)', '不能替代专业取证级清理工具', 'URL.revokeObjectURL(url)', '文件只在浏览器中处理']],
  ['tools/pdf-to-text.php', ['120*1024*1024', 'MAX_PAGES=500', 'MAX_TEXT_CHARS=5000000', 'MAX_TEXT_ITEMS_PER_PAGE=200000', 'getTextContent', 'visualText', 'contentStreamText', 'cancelRequested', '扫描图片', 'URL.revokeObjectURL(url)', '文件只在浏览器中处理']],
  ['tools/pdf-compress.php', ['120 * 1024 * 1024', 'setTimeout(() => URL.revokeObjectURL(url), 1000)']],
  ['tools/pdf-watermark.php', ['120*1024*1024', 'MAX_OUTPUT_BYTES=180*1024*1024', 'MAX_PAGES=300', 'MAX_WATERMARK_DRAWS=20000', 'makeWatermarkPng', 'pdf.embedPng', 'page.drawImage', '原有页面内容未栅格化', '预览暂不可用，但可正常下载', 'setTimeout(()=>URL.revokeObjectURL(url),1000)']],
  ['tools/pdf-to-image.php', ['120 * 1024 * 1024', 'URL.revokeObjectURL(previewUrl)', 'URL.revokeObjectURL(url)']],
]);
for (const [relative, markers] of hardenedPdfTools) {
  const source = fs.readFileSync(path.join(root, relative), 'utf8');
  for (const marker of markers) {
    if (!source.includes(marker)) errors.push(`${relative}：PDF 内存保护缺少 ${marker}`);
  }
}
const pdfWatermarkTool = fs.readFileSync(path.join(root, 'tools', 'pdf-watermark.php'), 'utf8');
if (pdfWatermarkTool.includes('window.jspdf') || pdfWatermarkTool.includes("toDataURL('image/jpeg'")) {
  errors.push('tools/pdf-watermark.php：仍会把完整 PDF 页面栅格化为 JPEG');
}
const themeScript = fs.readFileSync(path.join(root, 'js', 'theme.js'), 'utf8');
for (const marker of ['office_theme', 'prefers-color-scheme: dark', 'theme-dark', 'theme-toggle']) {
  if (!themeScript.includes(marker)) errors.push(`js/theme.js：主题切换缺少 ${marker}`);
}
if (!homepage.includes('js/theme.js') || !fs.readFileSync(path.join(root, 'tools', '_header.php'), 'utf8').includes('js/theme.js')) {
  errors.push('主题切换：主页或工具页未加载统一主题脚本');
}

if (errors.length) {
  console.error(errors.join('\n'));
  process.exit(1);
}

console.log(`项目检查通过：${files.filter((file) => file.endsWith('.php')).length} 个 PHP 文件，${checkedLinks.size} 个首页工具入口。`);
