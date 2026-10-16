import type { Register } from 'claude-code'

const deploys = [
  { key: 'deploy', label: 'Deploy', script: 'deploy.sh' },
  { key: 'deploy-fast', label: 'Deploy Fast', script: 'deploy-fast.sh' },
]

export const register: Register = on => {
  let isBusy = false

  on('ui.render', { component: 'AbovePrompt' }, async ($, e, next) => {
    if (e.props.hasSurvey || !(await $.fs.exists('deploy.sh'))) {
      return next(e)
    }

    const { Box, Button, Text } = $.ui.resolve(e)

    const rodar = async (label: string, script: string) => {
      if (isBusy) {
        $.ui.toast('Ja existe um deploy em andamento')
        return
      }
      isBusy = true
      $.ui.status(`Portal: ${label}...`)
      try {
        const r = await $.process.run(['bash', `./${script}`], { timeoutMs: 900000 })
        if (r.exitCode === 0) {
          $.ui.toast(`Portal ${label}: OK`)
        } else {
          const tail = (r.stderr || r.stdout).trim().split('\n').slice(-3).join(' | ')
          $.ui.toast(`Portal ${label} falhou: ${tail}`)
        }
      } catch (err) {
        $.ui.toast(`Portal ${label}: ${String(err)}`)
      } finally {
        isBusy = false
        $.ui.status(undefined)
      }
    }

    return (
      <Box>
        <Text dimColor>Deploy: </Text>
        {deploys.map(d => (
          <Button key={d.key} label={d.label} onPress={() => rodar(d.label, d.script)} />
        ))}
      </Box>
    )
  })
}
