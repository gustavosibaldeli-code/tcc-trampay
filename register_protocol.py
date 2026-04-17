import winreg as reg, os

# USE o pythonw.exe que você encontrou:
PYTHONW_PATH = r"C:\Users\asafe\AppData\Local\Microsoft\WindowsApps\pythonw.exe"
# Seu script Python:
SCRIPT_PATH  = r"D:\DOSCRIA\BUZO COSTA MAX\banco\login.py"
PROTOCOL     = "trampayagenda"

if not os.path.isfile(SCRIPT_PATH):
    raise FileNotFoundError(f"Script não encontrado: {SCRIPT_PATH}")

base = reg.CreateKey(reg.HKEY_CURRENT_USER, r"Software\Classes\\" + PROTOCOL)
reg.SetValueEx(base, None, 0, reg.REG_SZ, "URL:Trampay Agenda Protocol")
reg.SetValueEx(base, "URL Protocol", 0, reg.REG_SZ, "")

icon = reg.CreateKey(base, "DefaultIcon")
reg.SetValueEx(icon, None, 0, reg.REG_SZ, SCRIPT_PATH + ",1")

cmd = reg.CreateKey(base, r"shell\open\command")
# Chamando pythonw.exe explicitamente com o seu login.py e passando a URL (%1)
reg.SetValueEx(cmd, None, 0, reg.REG_SZ, f"\"{PYTHONW_PATH}\" \"{SCRIPT_PATH}\" \"%1\"")

print(f"Registrado: {PROTOCOL}:// -> {PYTHONW_PATH} {SCRIPT_PATH}")
print("Teste: Win+R -> trampayagenda://open?uid=123&nome=Teste")
