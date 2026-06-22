<?php
$title = 'Markdown 实时预览';
$desc = '实时将 Markdown 文本渲染为 HTML 预览。';
include '_header.php';
?>
            <div style="display:flex; gap:18px; flex-wrap:wrap;">
                <div class="tool-panel" style="flex:1 1 320px; min-width:280px;">
                    <label for="md-input">Markdown 输入</label>
                    <textarea id="md-input" style="min-height:380px;" placeholder="# 标题&#10;&#10;这是一段**粗体**和*斜体*文字。&#10;&#10;## 列表&#10;- 项目一&#10;- 项目二&#10;  - 子项目&#10;&#10;## 链接与代码&#10;[访问链接](https://example.com)&#10;&#10;```js&#10;console.log('hello');&#10;```&#10;&#10;行内 `code` 示例。"></textarea>
                </div>
                <div class="tool-panel" style="flex:1 1 320px; min-width:280px;">
                    <label>HTML 预览</label>
                    <div id="md-preview" class="preview-area" style="min-height:380px;"></div>
                </div>
            </div>
            <style>
            .preview-area h1 { font-size:24px; margin:14px 0 10px; color:#1e293b; border-bottom:2px solid #e2e8f0; padding-bottom:6px; }
            .preview-area h2 { font-size:20px; margin:14px 0 8px; color:#334155; }
            .preview-area h3 { font-size:17px; margin:12px 0 6px; color:#475569; }
            .preview-area p { margin:8px 0; line-height:1.7; color:#334155; }
            .preview-area ul, .preview-area ol { margin:8px 0 8px 24px; line-height:1.7; }
            .preview-area li { margin:4px 0; color:#334155; }
            .preview-area strong { color:#1e293b; }
            .preview-area em { color:#475569; }
            .preview-area a { color:#6366f1; text-decoration:none; }
            .preview-area a:hover { text-decoration:underline; }
            .preview-area code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-family:"Courier New",monospace; font-size:13px; color:#be185d; }
            .preview-area pre { background:#0f172a; color:#e2e8f0; padding:12px 14px; border-radius:8px; overflow-x:auto; margin:10px 0; font-family:"Courier New",monospace; font-size:13px; line-height:1.6; }
            .preview-area pre code { background:transparent; color:inherit; padding:0; }
            .preview-area blockquote { border-left:4px solid #6366f1; padding:4px 12px; margin:10px 0; color:#475569; background:#eef2ff; border-radius:4px; }
            </style>
            <script>
            (function() {
                const input = document.getElementById('md-input');
                const preview = document.getElementById('md-preview');

                function escapeHtml(s) {
                    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                }

                function parseInline(text) {
                    text = escapeHtml(text);
                    text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
                    text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
                    text = text.replace(/(^|[^*])\*([^*]+)\*/g, '$1<em>$2</em>');
                    text = text.replace(/__([^_]+)__/g, '<strong>$1</strong>');
                    text = text.replace(/(^|[^_])_([^_]+)_/g, '$1<em>$2</em>');
                    text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
                    return text;
                }

                function render(md) {
                    if (!md) return '<p style="color:#94a3b8;">在左侧输入 Markdown，这里将实时显示预览...</p>';
                    const lines = md.replace(/\r\n/g, '\n').split('\n');
                    let out = [];
                    let i = 0;
                    let inCode = false;
                    let codeBuf = [];
                    let listBuf = [];
                    let listType = null;

                    function flushList() {
                        if (listBuf.length) {
                            out.push('<' + listType + '>' + listBuf.join('') + '</' + listType + '>');
                            listBuf = []; listType = null;
                        }
                    }

                    while (i < lines.length) {
                        const line = lines[i];

                        if (inCode) {
                            if (/^\s*```/.test(line)) {
                                inCode = false;
                                out.push('<pre><code>' + escapeHtml(codeBuf.join('\n')) + '</code></pre>');
                                codeBuf = [];
                            } else {
                                codeBuf.push(line);
                            }
                            i++; continue;
                        }

                        if (/^\s*```/.test(line)) {
                            flushList();
                            inCode = true;
                            i++; continue;
                        }

                        let m;
                        if (m = line.match(/^(#+)\s+(.*)$/)) {
                            flushList();
                            const level = Math.min(m[1].length, 6);
                            out.push('<h' + level + '>' + parseInline(m[2]) + '</h' + level + '>');
                            i++; continue;
                        }

                        if (m = line.match(/^\s*([-*+])\s+(.*)$/)) {
                            if (listType !== 'ul') { flushList(); listType = 'ul'; }
                            listBuf.push('<li>' + parseInline(m[2]) + '</li>');
                            i++; continue;
                        }

                        if (m = line.match(/^\s*(\d+)\.\s+(.*)$/)) {
                            if (listType !== 'ol') { flushList(); listType = 'ol'; }
                            listBuf.push('<li>' + parseInline(m[2]) + '</li>');
                            i++; continue;
                        }

                        if (/^\s*>\s?/.test(line)) {
                            flushList();
                            const quoteLines = [];
                            while (i < lines.length && /^\s*>\s?/.test(lines[i])) {
                                quoteLines.push(lines[i].replace(/^\s*>\s?/, ''));
                                i++;
                            }
                            out.push('<blockquote>' + parseInline(quoteLines.join(' ')) + '</blockquote>');
                            continue;
                        }

                        if (line.trim() === '') {
                            flushList();
                            i++; continue;
                        }

                        flushList();
                        out.push('<p>' + parseInline(line) + '</p>');
                        i++;
                    }

                    flushList();
                    if (inCode && codeBuf.length) {
                        out.push('<pre><code>' + escapeHtml(codeBuf.join('\n')) + '</code></pre>');
                    }

                    return out.join('\n');
                }

                function update() {
                    preview.innerHTML = render(input.value);
                }

                input.addEventListener('input', update);
                update();
            })();
            </script>
<?php include '_footer.php'; ?>
