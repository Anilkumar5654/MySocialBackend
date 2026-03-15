<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Submit Copyright Claim</title>
    <style>
        :root { 
            --bg: #000; 
            --card: #0A0A0A; 
            --accent: #FF0055; /* SocialApp Pink */
            --border: #1A1A1A; 
            --text-main: #FFFFFF;
            --text-dim: #777777; 
            --input-bg: #111111; 
        }
        
        body { 
            background: var(--bg); 
            color: var(--text-main); 
            font-family: -apple-system, system-ui, sans-serif; 
            padding: 0; 
            margin: 0; 
            -webkit-font-smoothing: antialiased;
        }
        
        .container { padding: 20px 24px 100px 24px; }
        
        h2 { font-size: 28px; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.8px; }
        .subtitle { color: var(--text-dim); font-size: 14px; margin-bottom: 32px; line-height: 1.5; }

        .section-label { 
            font-size: 11px; 
            color: var(--accent); 
            text-transform: uppercase; 
            letter-spacing: 1.8px; 
            font-weight: 800; 
            margin-bottom: 12px; 
            display: block;
        }

        .input-group { margin-bottom: 28px; }

        /* Picker Styling with Neon Depth */
        .picker-box { 
            background: var(--card); 
            border: 1px solid var(--border); 
            padding: 16px; 
            border-radius: 22px; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 16px; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .picker-box:active { transform: scale(0.97); background: #151515; }
        
        .selected-thumb-wrapper {
            width: 70px;
            height: 40px;
            border-radius: 10px;
            background: #222;
            overflow: hidden;
            display: none;
            border: 1px solid var(--border);
        }
        
        .selected-thumb { width: 100%; height: 100%; object-fit: cover; }
        
        input, select, textarea { 
            width: 100%; 
            background: var(--input-bg); 
            border: 1px solid var(--border); 
            color: #fff; 
            padding: 18px; 
            border-radius: 20px; 
            box-sizing: border-box; 
            font-size: 16px; 
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        input:focus, textarea:focus { border-color: var(--accent); }

        /* YouTube Style Timestamp Design */
        .timestamp-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .ts-box { position: relative; }
        .ts-box input { text-align: center; font-weight: 700; color: var(--accent); letter-spacing: 1px; }
        .ts-hint { font-size: 10px; color: var(--text-dim); text-align: center; margin-top: 6px; font-weight: 600; text-transform: uppercase; }

        .btn { 
            background: var(--accent); 
            color: #fff; 
            border: none; 
            padding: 22px; 
            border-radius: 24px; 
            width: 100%; 
            font-weight: 800; 
            cursor: pointer; 
            font-size: 17px; 
            margin-top: 10px; 
            box-shadow: 0 12px 30px rgba(255, 0, 85, 0.25);
            transition: opacity 0.2s;
        }
        
        .btn:active { opacity: 0.8; }
        
        /* Modal UI Upgrade */
        #pickerModal { 
            display: none; 
            position: fixed; 
            inset: 0; 
            background: rgba(0,0,0,0.96); 
            z-index: 2000; 
            backdrop-filter: blur(20px); 
        }
        
        .modal-content { 
            background: #080808; 
            border-top: 1px solid var(--accent); 
            border-radius: 35px 35px 0 0; 
            height: 90vh; 
            position: absolute; 
            bottom: 0; 
            width: 100%; 
            display: flex; 
            flex-direction: column;
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        
        .modal-header {
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #111;
        }

        .video-item { 
            display: flex; 
            gap: 16px; 
            padding: 16px 24px; 
            border-bottom: 1px solid #0F0F0F; 
            align-items: center; 
        }
        
        .video-item:active { background: #111; }
        
        .video-item img { 
            width: 120px; 
            height: 68px; 
            border-radius: 16px; 
            object-fit: cover; 
            background: #1A1A1A; 
            border: 1px solid #222; 
        }
        
        .v-title { font-size: 15px; font-weight: 600; color: #fff; margin-bottom: 4px; line-height: 1.4; }
        .v-meta { font-size: 11px; color: var(--accent); font-weight: 700; text-transform: uppercase; }

        #closePicker { 
            background: #1A1A1A; 
            color: var(--accent); 
            padding: 8px 16px; 
            border-radius: 50px; 
            font-size: 12px; 
            font-weight: 800; 
            letter-spacing: 0.5px;
        }
        
        .search-container { padding: 12px 24px 20px 24px; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Report Content</h2>
        <p class="subtitle">Provide evidence of your original work to initiate a takedown request.</p>

        <form action="<?= base_url('api/creator/copyright/store') ?>" method="POST">
            <input type="hidden" name="user_id" id="form_user_id">
            <input type="hidden" name="content_id" value="<?= $content_id ?>">
            <input type="hidden" name="content_type" value="<?= strtoupper($type ?? 'VIDEO') ?>">
            <input type="hidden" name="original_content_id" id="orig_id">

            <div class="input-group">
                <span class="section-label">1. Evidence Source</span>
                <div class="picker-box" id="openPicker">
                    <div class="selected-thumb-wrapper" id="thumbWrapper">
                        <img id="previewThumb" class="selected-thumb">
                    </div>
                    <div style="flex:1">
                        <span id="displayText" style="color:var(--text-dim); font-weight: 500;">Select original content...</span>
                    </div>
                    <span style="color:var(--accent); font-weight: 800; font-size: 12px; letter-spacing: 1px;">CHOOSE</span>
                </div>
            </div>

            <div class="input-group">
                <span class="section-label">2. Conflict Period (MM:SS)</span>
                <div class="timestamp-row">
                    <div class="ts-box">
                        <input type="text" name="time_start" placeholder="00:00" pattern="[0-9]{2}:[0-9]{2}">
                        <div class="ts-hint">Start Point</div>
                    </div>
                    <div class="ts-box">
                        <input type="text" name="time_end" placeholder="00:00" pattern="[0-9]{2}:[0-9]{2}">
                        <div class="ts-hint">End Point</div>
                    </div>
                </div>
            </div>

            <div class="input-group">
                <span class="section-label">3. Proof Reference</span>
                <input type="url" name="evidence_url" id="evidence_url" readonly placeholder="Reference link will appear here" style="font-size: 13px; opacity: 0.5; color: var(--accent);">
            </div>

            <div class="input-group">
                <span class="section-label">4. Legal Reason</span>
                <select name="reason">
                    <option value="Full Re-upload">Full content re-upload</option>
                    <option value="Snippet Used">Snippet / Partial clip used</option>
                    <option value="Audio Violation">Original audio/music used</option>
                </select>
                <textarea name="description" rows="4" placeholder="Additional details to support your claim..."></textarea>
            </div>

            <button type="submit" class="btn">SUBMIT TAKEDOWN</button>
        </form>
    </div>

    <div id="pickerModal">
        <div class="modal-content">
            <div class="modal-header">
                <strong style="font-size:20px; letter-spacing: -0.5px;">Select Content</strong>
                <span id="closePicker">CANCEL</span>
            </div>
            <div class="search-container">
                <input type="text" id="vSearch" placeholder="Search your library..." style="background:#111; border:none; border-radius: 16px; padding: 15px;">
            </div>
            <div id="vList" style="flex:1; overflow-y:auto; padding-bottom:60px;">
                </div>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const activeUserId = urlParams.get('user_id') || urlParams.get('channel_id');
        document.getElementById('form_user_id').value = activeUserId;

        const modal = document.getElementById('pickerModal');
        const vList = document.getElementById('vList');
        const vSearch = document.getElementById('vSearch');
        const previewThumb = document.getElementById('previewThumb');
        const thumbWrapper = document.getElementById('thumbWrapper');

        document.getElementById('openPicker').onclick = () => { 
            modal.style.display = 'block'; 
            loadVideos(''); 
        };
        
        document.getElementById('closePicker').onclick = () => modal.style.display = 'none';
        vSearch.oninput = (e) => loadVideos(e.target.value);

        function loadVideos(q) {
            vList.innerHTML = '<div style="padding:60px; text-align:center; color:var(--accent); font-weight: 700; letter-spacing: 1px;">SYNCING...</div>';
            
            const type = "<?= $type ?? 'VIDEO' ?>";
            const baseUrl = "<?= base_url() ?>";

            fetch(`${baseUrl}/api/creator/copyright/my-content?user_id=${activeUserId}&search=${q}&type=${type}`)
                .then(r => r.json())
                .then(res => {
                    vList.innerHTML = '';
                    if(res.status && res.data.length > 0) {
                        res.data.forEach(v => {
                            let div = document.createElement('div');
                            div.className = 'video-item';
                            // Dynamic thumbnail pathing
                            let thumb = v.thumbnail_url;
                            
                            div.innerHTML = `
                                <img src="${thumb}">
                                <div style="flex:1">
                                    <div class="v-title">${v.title}</div>
                                    <div class="v-meta">UID: #${v.id}</div>
                                </div>
                            `;
                            div.onclick = () => {
                                document.getElementById('orig_id').value = v.id;
                                document.getElementById('displayText').innerText = v.title;
                                document.getElementById('displayText').style.color = "#FFFFFF";
                                document.getElementById('evidence_url').value = `${baseUrl}/v/${v.id}`;
                                previewThumb.src = thumb;
                                thumbWrapper.style.display = 'block';
                                modal.style.display = 'none';
                            };
                            vList.appendChild(div);
                        });
                    } else {
                        vList.innerHTML = '<p style="padding:60px; text-align:center; color:#444; font-weight: 600;">No content found.</p>';
                    }
                });
        }
    </script>
</body>
</html>

