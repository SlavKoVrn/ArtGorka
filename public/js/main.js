        // API Tester Application
        class APITester {
            constructor() {
                this.currentMethod = 'GET';
                this.history = [];
                this.init();
            }

            init() {
                this.bindEvents();
                this.loadHistory();
            }

            bindEvents() {
                // Method buttons
                document.querySelectorAll('.method-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active'));
                        e.target.classList.add('active');
                        this.currentMethod = e.target.dataset.method;
                        this.toggleBodyField();
                    });
                });

                // Tabs
                document.querySelectorAll('.tab').forEach(tab => {
                    tab.addEventListener('click', (e) => {
                        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                        e.target.classList.add('active');
                        document.getElementById(`${e.target.dataset.tab}-tab`).classList.add('active');
                    });
                });

                // Send button
                document.getElementById('sendBtn').addEventListener('click', () => this.sendRequest());

                // Copy button
                document.getElementById('copyBtn').addEventListener('click', () => this.copyResponse());

                // Initial body field toggle
                this.toggleBodyField();
            }

            toggleBodyField() {
                const bodyTab = document.querySelector('[data-tab="body"]');
                if (['GET', 'DELETE'].includes(this.currentMethod)) {
                    bodyTab.style.display = 'none';
                    if (bodyTab.classList.contains('active')) {
                        document.querySelector('[data-tab="headers"]').click();
                    }
                } else {
                    bodyTab.style.display = 'block';
                }
            }

            async sendRequest() {
                const url = document.getElementById('url').value.trim();
                const headersText = document.getElementById('headers').value.trim();
                const bodyText = document.getElementById('body').value.trim();

                if (!url) {
                    alert('Please enter a URL');
                    return;
                }

                let headers = {};
                let body = null;

                try {
                    if (headersText) {
                        headers = JSON.parse(headersText);
                    }
                } catch (e) {
                    alert('Invalid JSON in headers');
                    return;
                }

                try {
                    if (bodyText && !['GET', 'DELETE'].includes(this.currentMethod)) {
                        body = JSON.parse(bodyText);
                    }
                } catch (e) {
                    alert('Invalid JSON in body');
                    return;
                }

                // Show loading
                this.showLoading();

                const startTime = Date.now();

                try {
                    const options = {
                        method: this.currentMethod,
                        headers: {
                            'Content-Type': 'application/json',
                            ...headers
                        }
                    };

                    if (body && !['GET', 'DELETE'].includes(this.currentMethod)) {
                        options.body = JSON.stringify(body);
                    }

                    const response = await fetch(url, options);
                    const endTime = Date.now();
                    const duration = endTime - startTime;

                    const data = await response.json().catch(() => response.text());

                    this.displayResponse(response.status, data, duration);
                    this.addToHistory(url, this.currentMethod, response.status, duration);

                } catch (error) {
                    this.displayResponse(0, { error: error.message }, Date.now() - startTime);
                }
            }

            showLoading() {
                document.getElementById('responseSection').style.display = 'block';
                document.getElementById('responseBody').innerHTML = `
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Sending request...</p>
                    </div>
                `;
                document.getElementById('sendBtn').disabled = true;
            }

            displayResponse(status, data, duration) {
                const responseSection = document.getElementById('responseSection');
                const statusCode = document.getElementById('statusCode');
                const responseBody = document.getElementById('responseBody');

                responseSection.style.display = 'block';
                document.getElementById('sendBtn').disabled = false;

                // Status code styling
                statusCode.textContent = `${status} ${duration}ms`;
                statusCode.className = 'status-code';
                if (status >= 200 && status < 300) {
                    statusCode.classList.add('status-success');
                } else if (status >= 400) {
                    statusCode.classList.add('status-error');
                } else {
                    statusCode.classList.add('status-info');
                }

                // Format response
                if (typeof data === 'object') {
                    responseBody.textContent = JSON.stringify(data, null, 2);
                } else {
                    responseBody.textContent = data;
                }
            }

            addToHistory(url, method, status, duration) {
                const entry = {
                    url,
                    method,
                    status,
                    duration,
                    timestamp: new Date().toLocaleString()
                };

                this.history.unshift(entry);
                if (this.history.length > 10) {
                    this.history.pop();
                }

                localStorage.setItem('apiHistory', JSON.stringify(this.history));
                this.renderHistory();
            }

            loadHistory() {
                const saved = localStorage.getItem('apiHistory');
                if (saved) {
                    this.history = JSON.parse(saved);
                    this.renderHistory();
                }
            }

            renderHistory() {
                const historyList = document.getElementById('history-list');
                historyList.innerHTML = this.history.map((item, index) => `
                    <div class="history-item" onclick="apiTester.loadHistoryItem(${index})">
                        <strong>${item.method}</strong> - ${item.url}<br>
                        <small>Status: ${item.status} | Time: ${item.duration}ms | ${item.timestamp}</small>
                    </div>
                `).join('');
            }

            loadHistoryItem(index) {
                const item = this.history[index];
                document.getElementById('url').value = item.url;
                document.querySelector(`[data-method="${item.method}"]`).click();
            }

            copyResponse() {
                const text = document.getElementById('responseBody').textContent;
                navigator.clipboard.writeText(text).then(() => {
                    const copyBtn = document.getElementById('copyBtn');
                    copyBtn.textContent = 'Copied!';
                    setTimeout(() => copyBtn.textContent = 'Copy', 2000);
                });
            }
        }

        // Initialize the application
        const apiTester = new APITester();
