# 离线前端组件

本目录保留项目直接部署（例如宝塔同步源码）时所需的浏览器组件。Docker 镜像会在构建阶段用相同的固定版本重新生成它们。

| 文件 | 包与版本 | 许可证 | 上游项目 |
| --- | --- | --- | --- |
| `pdf.min.js`, `pdf.worker.min.js` | `pdfjs-dist@3.11.174` | Apache-2.0 | https://github.com/mozilla/pdf.js |
| `pdf-lib.min.js` | `pdf-lib@1.17.1` | MIT | https://github.com/Hopding/pdf-lib |
| `jspdf.umd.min.js` | `jspdf@2.5.1` | MIT | https://github.com/parallax/jsPDF |
| `jszip.min.js` | `jszip@3.10.1` | MIT OR GPL-3.0-or-later | https://github.com/Stuk/jszip |
| `qrcode.js` | `qrcode@1.5.4` + `esbuild@0.28.1` | MIT | https://github.com/soldair/node-qrcode |
| `hash-wasm.umd.min.js` | `hash-wasm@4.12.0` | MIT | https://github.com/Daninet/hash-wasm |

请勿手工修改压缩后的 JavaScript。需要升级时，应同步更新 `Dockerfile` 中的版本和 `scripts/check-project.mjs` 中的大小及 SHA-256。
