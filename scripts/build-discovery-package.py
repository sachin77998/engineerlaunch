from pathlib import Path
import subprocess,zipfile,json

root=Path(__file__).resolve().parent.parent
out=root/'release'/'job-discovery'
out.mkdir(parents=True,exist_ok=True)
changed=subprocess.check_output(['git','diff','--name-only'],cwd=root,text=True).splitlines()
untracked=subprocess.check_output(['git','ls-files','--others','--exclude-standard'],cwd=root,text=True).splitlines()
paths=sorted(set(p for p in changed+untracked if p.startswith(('app/','config/','database/seeders/','database/migrations/','resources/data/','resources/views/','routes/'))))
with zipfile.ZipFile(out/'website-update.zip','w',zipfile.ZIP_DEFLATED) as archive:
 for name in paths:archive.write(root/name,name)
(out/'INSTALL.md').write_text((root/'docs'/'JOB_DISCOVERY_DEPLOYMENT.md').read_text(encoding='utf-8'),encoding='utf-8')
# Account recovery SQL is generated separately and never included in Git.
print(json.dumps({'code_files':len(paths),'archive':str(out/'website-update.zip'),'files':paths},indent=2))
