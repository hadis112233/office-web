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

const dockerfile = fs.readFileSync(path.join(root, 'Dockerfile'), 'utf8');
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

const pdfToolRequirements = new Map([
  ['tools/pdf-merge.php', ['../static/vendor/pdf-lib.min.js']],
  ['tools/pdf-split.php', ['../static/vendor/pdf-lib.min.js']],
  ['tools/pdf-compress.php', ['../static/vendor/pdf.min.js', '../static/vendor/pdf.worker.min.js', '../static/vendor/jspdf.umd.min.js']],
  ['tools/pdf-watermark.php', ['../static/vendor/pdf.min.js', '../static/vendor/pdf.worker.min.js', '../static/vendor/jspdf.umd.min.js']],
  ['tools/pdf-to-image.php', ['../static/vendor/pdf.min.js', '../static/vendor/pdf.worker.min.js', '../static/vendor/jszip.min.js']],
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
for (const marker of ['validSession', "['front', 'back']", "$uploadRoot . $session"]) {
  if (!uploadApi.includes(marker)) errors.push(`api/upload.php：身份证上传隔离缺少 ${marker}`);
}
const idcardMobile = fs.readFileSync(path.join(root, 'tools', 'idcard-mobile.php'), 'utf8');
if (!idcardMobile.includes("formData.append('session', uploadSession)")) {
  errors.push('tools/idcard-mobile.php：手机上传未携带配对码');
}
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

if (errors.length) {
  console.error(errors.join('\n'));
  process.exit(1);
}

console.log(`项目检查通过：${files.filter((file) => file.endsWith('.php')).length} 个 PHP 文件，${checkedLinks.size} 个首页工具入口。`);
